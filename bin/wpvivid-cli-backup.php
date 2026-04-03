<?php
/**
 * Backup completo (ficheiros + BD) via WPvivid — apenas CLI.
 * Uso: php -d max_execution_time=0 -d memory_limit=512M bin/wpvivid-cli-backup.php
 *
 * @package WCB
 */

if ( php_sapi_name() !== 'cli' ) {
	exit( 'CLI only' );
}

$wp_root = dirname( __DIR__ );
require $wp_root . '/wp-load.php';

$admins = get_users(
	[
		'role'    => 'administrator',
		'number'  => 1,
		'orderby' => 'ID',
		'order'   => 'ASC',
	]
);
if ( empty( $admins ) ) {
	fwrite( STDERR, "Erro: nenhum utilizador administrador.\n" );
	exit( 1 );
}
wp_set_current_user( $admins[0]->ID );

if ( ! class_exists( 'WPvivid_Backup_2', false ) ) {
	fwrite( STDERR, "Erro: plugin WPvivid inativo ou inexistente.\n" );
	exit( 1 );
}

global $wpvivid_plugin;
if ( empty( $wpvivid_plugin ) || ! is_object( $wpvivid_plugin ) ) {
	fwrite( STDERR, "Erro: instância WPvivid não carregada.\n" );
	exit( 1 );
}

$backup_options = [
	'type'         => 'Manual',
	'backup_files' => 'files+db',
	'local'        => '1',
	'remote'       => '0',
];

do_action( 'wpvivid_clean_oldest_backup', $backup_options );
if ( apply_filters( 'wpvivid_need_clean_oldest_backup', true, $backup_options ) && method_exists( $wpvivid_plugin, 'clean_oldest_backup' ) ) {
	$wpvivid_plugin->clean_oldest_backup();
}

$runner = new WPvivid_Backup_2();
if ( $runner->is_tasks_backup_running() ) {
	fwrite( STDERR, "Erro: já existe uma tarefa de backup em execução.\n" );
	exit( 1 );
}

$settings  = $runner->get_backup_settings( $backup_options );
$task_boot = new WPvivid_Backup_Task_2();
ob_start();
$ret = $task_boot->new_backup_task( $backup_options, $settings );
ob_end_clean();

if ( ! isset( $ret['result'] ) || $ret['result'] !== 'success' || empty( $ret['task_id'] ) ) {
	fwrite( STDERR, 'Erro ao criar tarefa: ' . wp_json_encode( $ret ) . "\n" );
	exit( 1 );
}

$task_id = $ret['task_id'];
fwrite( STDOUT, "Tarefa criada: {$task_id}\n" );

$runner->end_shutdown_function = false;
register_shutdown_function( [ $runner, 'deal_backup_shutdown_error' ] );

try {
	$runner->update_backup_task_status( $task_id, true, 'running' );
	$wpvivid_plugin->flush( $task_id );
	$runner->add_monitor_event( $task_id );
	$runner->current_task_id = $task_id;
	$runner->task            = new WPvivid_Backup_Task_2( $task_id );
	$runner->task->set_memory_limit();
	$runner->task->set_time_limit();

	$wpvivid_plugin->wpvivid_log->OpenLogFile( $runner->task->task['options']['log_file_name'] );
	$wpvivid_plugin->wpvivid_log->WriteLog( 'Início backup CLI (WCB).', 'notice' );
	$wpvivid_plugin->wpvivid_log->WriteLogHander();

	if ( ! $runner->task->is_backup_finished() ) {
		$bret = $runner->backup();
		$runner->task->clear_cache();
		if ( $bret['result'] !== 'success' ) {
			$err = isset( $bret['error'] ) ? $bret['error'] : 'desconhecido';
			fwrite( STDERR, "Erro durante backup: {$err}\n" );
			$runner->update_backup_task_status( $task_id, false, 'error', false, false, $err );
			do_action( 'wpvivid_handle_backup_2_failed', $task_id );
			$runner->clear_monitor_schedule( $task_id );
			$runner->end_shutdown_function = true;
			exit( 1 );
		}
	}

	if ( $runner->task->need_upload() ) {
		$uret = $runner->upload( $task_id );
		if ( $uret['result'] === WPVIVID_SUCCESS ) {
			do_action( 'wpvivid_handle_backup_2_succeed', $task_id );
			$runner->update_backup_task_status( $task_id, false, 'completed' );
		} else {
			$err = isset( $uret['error'] ) ? $uret['error'] : 'upload falhou';
			fwrite( STDERR, "Erro no upload remoto: {$err}\n" );
			do_action( 'wpvivid_handle_backup_2_failed', $task_id );
			$runner->clear_monitor_schedule( $task_id );
			$runner->end_shutdown_function = true;
			exit( 1 );
		}
	} else {
		$wpvivid_plugin->wpvivid_log->WriteLog( 'Backup concluído.', 'notice' );
		do_action( 'wpvivid_handle_backup_2_succeed', $task_id );
		$runner->update_backup_task_status( $task_id, false, 'completed' );
	}
	$runner->clear_monitor_schedule( $task_id );
} catch ( Throwable $e ) {
	fwrite( STDERR, 'Exceção: ' . $e->getMessage() . "\n" );
	$runner->end_shutdown_function = true;
	exit( 1 );
}

$runner->end_shutdown_function = true;
fwrite( STDOUT, "OK. Saída típica: wp-content/wpvividbackups/\n" );
exit( 0 );
