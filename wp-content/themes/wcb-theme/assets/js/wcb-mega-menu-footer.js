document.addEventListener('DOMContentLoaded', function () {
      var megaItems = document.querySelectorAll('.wcb-nav__item.wcb-nav__item--mega');

      megaItems.forEach(function (megaItem) {
        var trigger = megaItem.querySelector(':scope > .wcb-nav__link');
        var mega = megaItem.querySelector(':scope > .wcb-mega');
        var inner = mega ? mega.querySelector('.wcb-mega__inner') : null;
        var list = inner ? inner.querySelector('.wcb-mega__simple') : null;

        if (!trigger || !mega || !inner || !list) return;

        var columns = list.querySelectorAll(':scope > li');

        /* ---------------------------------------
           1. Header row do submenu
           --------------------------------------- */
        if (!inner.querySelector(':scope > .wcb-mega__header')) {
          var rawLabel = (trigger.textContent || '').replace(/[\u2304\u2334\u25BE\u25BC]/g, '').trim();
          var categoryName = rawLabel || 'Categoria';
          var categoryHref = trigger.getAttribute('href') || '#';
          var columnCount = columns.length;

          var header = document.createElement('div');
          header.className = 'wcb-mega__header';

          var title = document.createElement('h3');
          title.className = 'wcb-mega__title';
          title.textContent = categoryName;

          var count = document.createElement('span');
          count.className = 'wcb-mega__count';
          count.textContent = '\u2014 ' + columnCount + ' grupo' + (columnCount !== 1 ? 's' : '');

          var viewAll = document.createElement('a');
          viewAll.className = 'wcb-mega__view-all-top';
          viewAll.href = categoryHref;
          viewAll.textContent = 'Ver todos de ' + categoryName;

          header.appendChild(title);
          header.appendChild(count);
          header.appendChild(viewAll);

          inner.insertBefore(header, list);
        }

        /* ---------------------------------------
           2. Injeção do "VER TODOS" por coluna
              apenas em colunas com filhos
           --------------------------------------- */
        var childColumns = list.querySelectorAll(':scope > li.menu-item-has-children');

        childColumns.forEach(function (col) {
          var headingLink = col.querySelector(':scope > .wcb-mega__link--has-sub');
          var subList = col.querySelector(':scope > .wcb-mega__sub');
          var cta = col.querySelector(':scope > .wcb-mega__ver-todos');

          if (!headingLink || !subList) return;

          if (!cta) {
            cta = document.createElement('a');
            cta.className = 'wcb-mega__ver-todos';
            cta.href = headingLink.getAttribute('href') || '#';
            cta.textContent = 'Ver Todos';
            col.appendChild(cta);
          } else {
            if (!cta.getAttribute('href')) {
              cta.href = headingLink.getAttribute('href') || '#';
            }
            cta.textContent = 'Ver Todos';
          }
        });

        /* ---------------------------------------
           3. Grid ponderado + classe utilitária
              Colunas com filhos = 2fr
              Colunas sem filhos = 1fr
           --------------------------------------- */
        var totalCols = columns.length;
        var frValues = [];
        columns.forEach(function(col) {
          frValues.push(col.classList.contains('menu-item-has-children') ? '2fr' : '1fr');
        });
        list.style.gridTemplateColumns = frValues.join(' ');

        // Classe utilitária para fallback
        list.classList.remove('wcb-mega__simple--cols-2', 'wcb-mega__simple--cols-3', 'wcb-mega__simple--cols-4', 'wcb-mega__simple--cols-5', 'wcb-mega__simple--cols-6');
        list.classList.add('wcb-mega__simple--cols-' + totalCols);

        /* ---------------------------------------
           4. Remoção de prefixos redundantes
              Ex: "Juice Adocicado" → "Adocicado"
              quando o pai é "Perfil de Sabor"
              e o avô é "Juices"
           --------------------------------------- */
        childColumns.forEach(function(col) {
          var parentLink = col.querySelector(':scope > .wcb-mega__link--has-sub');
          if (!parentLink) return;

          var parentName = parentLink.textContent.trim();
          var grandParentName = (trigger.textContent || '').replace(/[\u2304\u2334\u25BE\u25BC]/g, '').trim();

          var subLinks = col.querySelectorAll('.wcb-mega__sub .wcb-mega__link');
          subLinks.forEach(function(link) {
            var originalText = link.textContent.trim();
            var newText = originalText;

            // Tentar remover prefixo do grandParent (ex: "Juice", "SaltNic")
            var prefixes = [grandParentName];
            // Gerar variações (singular, sem "s" final, etc.)
            if (grandParentName.length > 2) {
              var singular = grandParentName.replace(/s$/i, '');
              if (singular !== grandParentName) prefixes.push(singular);
            }

            for (var i = 0; i < prefixes.length; i++) {
              var prefix = prefixes[i];
              if (prefix && newText.length > prefix.length + 2 &&
                  newText.toLowerCase().indexOf(prefix.toLowerCase()) === 0) {
                newText = newText.substring(prefix.length).replace(/^[\s\-–—]+/, '').trim();
                break;
              }
            }

            if (newText && newText !== originalText && newText.length > 1) {
              // Capitalizar primeira letra
              link.textContent = newText.charAt(0).toUpperCase() + newText.slice(1);
            }
          });
        });

        /* ---------------------------------------
           5. Stagger animation nas colunas
           --------------------------------------- */
        columns.forEach(function(col, idx) {
          col.style.transitionDelay = (idx * 0.04) + 's';
        });

        /* ---------------------------------------
           6. Drill-down: "VER TODOS" expande
              a coluna e esconde as outras
           --------------------------------------- */
        (function setupDrillDown(megaInner, simpleList) {
          megaInner.addEventListener('click', function(e) {
            var btn = e.target.closest('.wcb-mega__ver-todos');
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();

            var clickedCol = btn.closest('li');
            if (!clickedCol) return;

            var allCols = simpleList.querySelectorAll(':scope > li');
            var headingLink = clickedCol.querySelector('.wcb-mega__link--has-sub');
            var colName = headingLink ? headingLink.textContent.trim() : '';

            // Save original header content
            var headerEl = megaInner.querySelector('.wcb-mega__header');
            if (headerEl && !headerEl.dataset.originalHtml) {
              headerEl.dataset.originalHtml = headerEl.innerHTML;
            }

            // Enter drill-down mode
            megaInner.classList.add('wcb-mega--drilled');

            // Hide other columns with animation
            allCols.forEach(function(col) {
              if (col !== clickedCol) {
                col.classList.add('wcb-mega__col--hidden');
              }
            });

            // Expand clicked column
            clickedCol.classList.add('wcb-mega__col--drilled');

            // Hide the "VER TODOS" button within the drilled column
            btn.style.display = 'none';

            // Update header with back button and category name
            if (headerEl) {
              headerEl.innerHTML = '';

              var backBtn = document.createElement('button');
              backBtn.className = 'wcb-mega__back';
              backBtn.setAttribute('type', 'button');
              backBtn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg> Voltar';

              var drilledTitle = document.createElement('h3');
              drilledTitle.className = 'wcb-mega__title';
              drilledTitle.textContent = colName;

              var drilledLink = document.createElement('a');
              drilledLink.className = 'wcb-mega__view-all-top';
              drilledLink.href = headingLink ? headingLink.getAttribute('href') : '#';
              drilledLink.textContent = 'Ver página de ' + colName;

              headerEl.appendChild(backBtn);
              headerEl.appendChild(drilledTitle);
              headerEl.appendChild(drilledLink);

              // Back button handler
              backBtn.addEventListener('click', function(ev) {
                ev.preventDefault();
                ev.stopPropagation();

                megaInner.classList.remove('wcb-mega--drilled');

                allCols.forEach(function(col) {
                  col.classList.remove('wcb-mega__col--hidden');
                });
                clickedCol.classList.remove('wcb-mega__col--drilled');
                btn.style.display = '';

                // Restore original header
                if (headerEl.dataset.originalHtml) {
                  headerEl.innerHTML = headerEl.dataset.originalHtml;
                }
              });
            }
          });

          // Reset drill-down when mega menu closes
          megaItem.addEventListener('mouseleave', function() {
            setTimeout(function() {
              if (!megaItem.matches(':hover') && megaInner.classList.contains('wcb-mega--drilled')) {
                megaInner.classList.remove('wcb-mega--drilled');
                var allCols = simpleList.querySelectorAll(':scope > li');
                allCols.forEach(function(col) {
                  col.classList.remove('wcb-mega__col--hidden', 'wcb-mega__col--drilled');
                });
                // Restore VER TODOS buttons
                simpleList.querySelectorAll('.wcb-mega__ver-todos').forEach(function(b) {
                  b.style.display = '';
                });
                // Restore header
                var headerEl = megaInner.querySelector('.wcb-mega__header');
                if (headerEl && headerEl.dataset.originalHtml) {
                  headerEl.innerHTML = headerEl.dataset.originalHtml;
                }
              }
            }, 300);
          });
        })(inner, list);
      });

    });
