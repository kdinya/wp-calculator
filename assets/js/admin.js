(function() {
        const AJAX_URL = (window.WOOD_CALC_BOOTSTRAP && window.WOOD_CALC_BOOTSTRAP.ajax_url) || (window.ajaxurl || '/wp-admin/admin-ajax.php');
        const NONCE = (window.WOOD_CALC_BOOTSTRAP && window.WOOD_CALC_BOOTSTRAP.nonce) || (document.getElementById('wood-calc-nonce') || {}).value || '';
        const LOCAL_STORAGE_KEY = 'wood_calc_local_mirror_v2';
        const LANG_STORAGE_KEY = 'wood_calc_lang';
        let isDataInitialized = false;
        function showToast(message, type) {
            type = type || 'info';
            let container = document.getElementById('wc-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'wc-toast-container';
                container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999999;display:flex;flex-direction:column;gap:8px;pointer-events:none;';
                document.body.appendChild(container);
            }
            const toast = document.createElement('div');
            const borderColor = type === 'error' ? '#dc2626' : (type === 'success' ? '#95b504' : '#38bdf8');
            const icon = type === 'error' ? '⚠️' : (type === 'success' ? '✅' : 'ℹ️');
            toast.style.cssText = 'background:#24272a;color:#ffffff;padding:10px 18px;border-radius:8px;font-size:13px;font-weight:600;box-shadow:0 10px 25px rgba(0,0,0,0.25);display:flex;align-items:center;gap:10px;pointer-events:auto;transition:opacity 0.25s ease, transform 0.25s ease;transform:translateY(8px);opacity:0;border-left:4px solid ' + borderColor + ';';
            toast.innerHTML = '<span>' + icon + '</span><span>' + escapeHtml(message) + '</span>';
            container.appendChild(toast);
            requestAnimationFrame(function() {
                toast.style.transform = 'translateY(0)';
                toast.style.opacity = '1';
            });
            setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(8px)';
                setTimeout(function() { toast.remove(); }, 260);
            }, 3500);
        }


        const I18N = {
            uk: {
                tab_calc: "Калькулятор",
                tab_settings: "Налаштування",
                header_title: "🛠️ Розрахунок вартості виробів",
                btn_backup: "💾 Бекап",
                syncing: "● Синхронізація...",
                saved: "● Збережено в WordPress",
                offline: "● Локальний режим",
                sec1_title: "1. Швидкий калькулятор вартості",
                lbl_calc_mat: "Матеріал (тариф за 1 см²)",
                lbl_calc_len: "Довжина (мм)",
                lbl_calc_width: "Ширина (мм)",
                btn_send_to_form: "Внести у виріб ↓",
                res_area: "Розрахована площа:",
                res_price: "Ціна за 1 шт:",
                sec2_title: "2. Додати виріб у список",
                lbl_add_name: "Назва виробу",
                lbl_add_mat: "Оберіть матеріал",
                lbl_add_price: "Ціна/шт (грн)",
                btn_add_save: "+ Зберегти",
                sec3_title: "3. Список виробів",
                invoice_title: "РОЗРАХУНОК ЗАМОВЛЕННЯ",
                chk_hide_unselected: "Сховати невиділені",
                btn_select_all: "Виділити всі",
                btn_deselect_all: "Зняти всі",
                inv_cols_title: "Колонки для накладної:",
                col_photo: "Фото",
                col_material: "Матеріал",
                col_dims: "Розміри",
                col_price_pc: "Ціна / 1 шт",
                col_qty: "К-сть",
                col_sum: "Сума",
                col_actions: "Дії",
                summary_selected: "Обрано виробів:",
                summary_qty: "Загальна к-сть:",
                summary_sum: "Загальна сума:",
                btn_invoice_mode: "📸 Накладна для скріна",
                btn_exit_invoice: "← Вийти з режиму накладної",
                sec_materials_title: "📦 Довідник матеріалів",
                lbl_mat_name: "Назва матеріалу",
                lbl_mat_rate: "Тариф за 1 см² (грн)",
                btn_mat_add: "+ Додати",
                settings_title: "⚙️ Налаштування калькулятора",
                settings_lang: "Мова інтерфейсу",
                settings_lang_desc: "Обрана мова зберігається автоматично та використовується для калькулятора, каталогу і накладної.",
                updater_title: "🚀 Оновлення плагіна з GitHub",
                lbl_wipe_uninstall: "Видаляти всі дані та налаштування при повному видаленні плагіна",
                desc_wipe_uninstall: "Якщо вимкнено — ваші створені матеріали, каталог виробів та налаштування збережуться навіть після деінсталяції плагіна.",
                tab_appearance: "Оформлення",
                backup_title: "💾 Резервне копіювання даних",
                backup_desc: "Збережіть повну резервну копію ваших матеріалів, виробів та налаштувань у файлі JSON для безпеки або перенесення.",
                appearance_title: "🎨 Зовнішній вигляд",
                lbl_accent_color: "Акцентний колір кнопок та активних елементів",
                lbl_custom_color: "Довільний колір:",
                appearance_desc: "Обраний колір застосовується до кнопок, чекбоксів, підсвітки та акцентних рамок інтерфейсу. Вибір зберігається автоматично.",
                lbl_current_ver: "Поточна версія:",
                lbl_latest_ver: "Остання в GitHub:",
                btn_check_update: "Перевірити оновлення",
                btn_apply_update: "Оновити плагін зараз",
                lbl_changelog: "Зміни в релізі:",
                checking_update: "Перевіряємо релізи GitHub...",
                update_latest: "У вас встановлено найновішу версію!",
                update_found: "Знайдено нову версію: ",
                update_reinstall_ok: "Версія актуальна. Можна перевстановити за потреби.",
                updating_in_progress: "Завантаження та встановлення оновлення...",
                update_success: "Плагін успішно оновлено! Перезавантажте сторінку.",
                modal_edit_prod_title: "Редагувати виріб",
                modal_edit_mat_title: "Редагувати матеріал",
                modal_del_title: "Підтвердження видалення",
                modal_del_text: "Ви дійсно бажаєте видалити цей елемент? Цю дію неможливо буде скасувати.",
                btn_cancel: "Скасувати",
                btn_save: "Зберегти зміни",
                btn_delete: "Видалити",
                curr: "грн",
                sq_cm: "см²",
                pcs: "шт",
                no_items: "Немає створених виробів",
                no_materials: "Матеріали відсутні. Додайте перший матеріал у довіднику.",
                enter_valid_name: "Будь ласка, введіть коректну назву та розміри",
                enter_valid_mat: "Будь ласка, введіть назву породи та коректний тариф"
            },
            en: {
                tab_calc: "Calculator",
                tab_settings: "Settings",
                header_title: "🛠️ Wood Product Cost Calculator",
                btn_backup: "💾 Backup",
                syncing: "● Syncing...",
                saved: "● Saved to WordPress",
                offline: "● Offline mode",
                sec1_title: "1. Quick Cost Calculator",
                lbl_calc_mat: "Material (rate per 1 cm²)",
                lbl_calc_len: "Length (mm)",
                lbl_calc_width: "Width (mm)",
                btn_send_to_form: "Send to product ↓",
                res_area: "Calculated area:",
                res_price: "Price per 1 pc:",
                sec2_title: "2. Add Product to List",
                lbl_add_name: "Product name",
                lbl_add_mat: "Choose material",
                lbl_add_price: "Price/pc (UAH)",
                btn_add_save: "+ Save",
                sec3_title: "3. Product Catalog",
                invoice_title: "ORDER CALCULATION",
                chk_hide_unselected: "Hide unselected",
                btn_select_all: "Select all",
                btn_deselect_all: "Deselect all",
                inv_cols_title: "Invoice columns:",
                col_photo: "Photo",
                col_material: "Material",
                col_dims: "Dimensions",
                col_price_pc: "Price / 1 pc",
                col_qty: "Qty",
                col_sum: "Sum",
                col_actions: "Actions",
                summary_selected: "Selected items:",
                summary_qty: "Total qty:",
                summary_sum: "Total sum:",
                btn_invoice_mode: "📸 Invoice View for Screenshot",
                btn_exit_invoice: "← Exit Invoice View",
                sec_materials_title: "📦 Material Directory",
                lbl_mat_name: "Wood type name",
                lbl_mat_rate: "Rate per 1 cm² (UAH)",
                btn_mat_add: "+ Add",
                settings_title: "⚙️ Calculator Settings",
                settings_lang: "Interface Language",
                settings_lang_desc: "Selected language is saved automatically and used for calculator, catalog, and invoice.",
                updater_title: "🚀 GitHub Plugin Updates",
                lbl_wipe_uninstall: "Delete all data and settings on plugin uninstallation",
                desc_wipe_uninstall: "If disabled, your created materials, products catalog, and settings are preserved even after plugin uninstall.",
                tab_appearance: "Appearance",
                backup_title: "💾 Data Backup",
                backup_desc: "Save a complete backup of your materials, items, and settings in JSON format for security or migration.",
                appearance_title: "🎨 Appearance",
                lbl_accent_color: "Accent color for buttons and active elements",
                lbl_custom_color: "Custom color:",
                appearance_desc: "The selected color is applied to buttons, checkboxes, highlights and accent borders. Your choice is saved automatically.",
                lbl_current_ver: "Current version:",
                lbl_latest_ver: "Latest on GitHub:",
                btn_check_update: "Check for Updates",
                btn_apply_update: "Update Plugin Now",
                lbl_changelog: "Release Changelog:",
                checking_update: "Checking GitHub releases...",
                update_latest: "You have the latest version installed!",
                update_found: "New version available: ",
                update_reinstall_ok: "Version is up to date. Reinstallation available if needed.",
                updating_in_progress: "Downloading and updating plugin...",
                update_success: "Plugin successfully updated! Please refresh the page.",
                modal_edit_prod_title: "Edit Product",
                modal_edit_mat_title: "Edit Material",
                modal_del_title: "Confirm Deletion",
                modal_del_text: "Are you sure you want to delete this item? This action cannot be undone.",
                btn_cancel: "Cancel",
                btn_save: "Save Changes",
                btn_delete: "Delete",
                curr: "UAH",
                sq_cm: "cm²",
                pcs: "pcs",
                no_items: "No products created yet",
                no_materials: "No materials added yet. Please add a material in directory.",
                enter_valid_name: "Please enter valid name and dimensions",
                enter_valid_mat: "Please enter wood name and valid rate"
            }
        };

        let currentLang = 'uk';
        let materials = [];
        let items = [];
        let columnVisibility = {
            photo: true,
            mat: true,
            dims: true,
            price: true,
            qty: true,
            sum: true
        };

        function t(key) {
            if (I18N[currentLang] && I18N[currentLang][key]) {
                return I18N[currentLang][key];
            }
            if (I18N['uk'] && I18N['uk'][key]) {
                return I18N['uk'][key];
            }
            return key;
        }

        function applyLanguageToDom() {
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const k = el.getAttribute('data-i18n');
                if (k && I18N[currentLang] && I18N[currentLang][k]) {
                    el.textContent = I18N[currentLang][k];
                }
            });

            const radioUk = document.getElementById('wc-lang-uk');
            const radioEn = document.getElementById('wc-lang-en');
            if (radioUk && radioEn) {
                radioUk.checked = (currentLang === 'uk');
                radioEn.checked = (currentLang === 'en');
            }

            const addName = document.getElementById('add-name');
            if (addName) addName.placeholder = currentLang === 'uk' ? 'напр. Дошка дубова' : 'e.g. Oak Board';
            const matName = document.getElementById('mat-name');
            if (matName) matName.placeholder = currentLang === 'uk' ? 'напр. Дуб селект' : 'e.g. Premium Oak';

            renderMaterials();
            renderItems();
            updateCalculations();
        }

        const COLOR_PRESETS = [
            { name: 'tomchik', labelUk: 'Фірмовий tomchik', labelEn: 'Tomchik brand', color: '#95b504' },
            { name: 'graphite', labelUk: 'Темний графіт', labelEn: 'Dark graphite', color: '#24272a' },
            { name: 'red', labelUk: 'Червоний', labelEn: 'Red', color: '#dc2626' },
            { name: 'blue', labelUk: 'Класичний синій', labelEn: 'Classic blue', color: '#2563eb' },
            { name: 'indigo', labelUk: 'Фіолетовий / Індиго', labelEn: 'Purple / Indigo', color: '#7c3aed' },
            { name: 'orange', labelUk: 'Бурштиновий', labelEn: 'Amber / Orange', color: '#ea580c' }
        ];

        let accentColor = '#95b504';
        let wipeOnUninstall = false;

        window.toggleWipeOnUninstall = function(enabled) {
            wipeOnUninstall = enabled;
            saveData(true);
        };

        function applyAccentColor() {
            document.documentElement.style.setProperty('--tc-green', accentColor);
            document.documentElement.style.setProperty('--tc-green-dark', shadeColor(accentColor, -15));
            document.documentElement.style.setProperty('--tc-green-light', tintLightColor(accentColor));
            const appEl = document.getElementById('wood-calculator-app');
            if (appEl) {
                appEl.style.setProperty('--tc-green', accentColor);
                appEl.style.setProperty('--tc-green-dark', shadeColor(accentColor, -15));
                appEl.style.setProperty('--tc-green-light', tintLightColor(accentColor));
            }
        }

        function shadeColor(hex, percent) {
            const num = parseInt(hex.slice(1), 16);
            const r = (num >> 16) + percent;
            const g = ((num >> 8) & 0x00FF) + percent;
            const b = (num & 0x0000FF) + percent;
            const clamp = (v) => Math.max(0, Math.min(255, v));
            return '#' + ((clamp(r) << 16) | (clamp(g) << 8) | clamp(b)).toString(16).padStart(6, '0');
        }

        function tintLightColor(hex) {
            const num = parseInt(hex.slice(1), 16);
            const r = (num >> 16) & 0x00FF;
            const g = (num >> 8) & 0x00FF;
            const b = num & 0x0000FF;
            const lighten = (v) => Math.round(v + (255 - v) * 0.9);
            return '#' + ((lighten(r) << 16) | (lighten(g) << 8) | lighten(b)).toString(16).padStart(6, '0');
        }

        function renderColorPresets() {
            const container = document.getElementById('wc-color-presets');
            if (!container) return;
            container.innerHTML = COLOR_PRESETS.map(p => {
                const isActive = p.color.toLowerCase() === accentColor.toLowerCase();
                return `<button type="button" class="btn ${isActive ? 'btn-dark' : 'btn-outline'} btn-sm" style="gap:6px;" onclick="setAccentColor('${p.color}')">
                            <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:${p.color};"></span>
                            <span>${currentLang === 'uk' ? p.labelUk : p.labelEn}</span>
                        </button>`;
            }).join('');
            const customInput = document.getElementById('wc-custom-color');
            if (customInput) customInput.value = accentColor;
        }

        window.setAccentColor = function(color) {
            if (!/^#[0-9a-fA-F]{6}$/.test(color)) return;
            accentColor = color;
            applyAccentColor();
            renderColorPresets();
            saveData(true);
        };

        window.setWcLanguage = function(lang) {
            if (lang !== 'uk' && lang !== 'en') return;
            currentLang = lang;
            localStorage.setItem(LANG_STORAGE_KEY, lang);
            applyLanguageToDom();
            saveData(true);
        };

        window.switchWcTab = function(tabName) {
            const panes = {
                calc: document.getElementById('wc-tab-pane-calc'),
                appearance: document.getElementById('wc-tab-pane-appearance'),
                settings: document.getElementById('wc-tab-pane-settings')
            };
            const buttons = {
                calc: document.getElementById('tab-nav-calc'),
                appearance: document.getElementById('tab-nav-appearance'),
                settings: document.getElementById('tab-nav-settings')
            };

            Object.keys(panes).forEach(key => {
                if (panes[key]) panes[key].style.display = (key === tabName) ? 'block' : 'none';
                if (buttons[key]) {
                    if (key === tabName) buttons[key].classList.add('active');
                    else buttons[key].classList.remove('active');
                }
            });

            if (tabName === 'appearance') {
                renderColorPresets();
            }
        };

        // GitHub Updater JS
        window.checkGitHubUpdate = function() {
            const statusEl = document.getElementById('update-status-msg');
            const btnRun = document.getElementById('btn-run-update');
            const latestBox = document.getElementById('wc-latest-ver-box');
            const changelogBox = document.getElementById('update-changelog-box');
            const changelogText = document.getElementById('update-changelog-text');

            if (statusEl) statusEl.textContent = t('checking_update');
            if (btnRun) btnRun.style.display = 'none';

            fetch(AJAX_URL + '?action=wood_calc_check_update&nonce=' + encodeURIComponent(NONCE))
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data) {
                        const d = res.data;
                        if (latestBox) {
                            latestBox.innerHTML = `<strong>${t('lbl_latest_ver')}</strong> <code>v${escapeHtml(d.latest_version)}</code>`;
                        }

                        if (d.update_available) {
                            if (statusEl) statusEl.textContent = t('update_found') + 'v' + d.latest_version;
                            if (btnRun) btnRun.style.display = 'inline-flex';
                        } else {
                            if (statusEl) statusEl.textContent = d.can_reinstall ? t('update_reinstall_ok') : t('update_latest');
                            if (d.can_reinstall && btnRun) {
                                btnRun.style.display = 'inline-flex';
                                btnRun.textContent = '⚡ ' + (currentLang === 'uk' ? 'Перевстановити v' : 'Reinstall v') + d.latest_version;
                            }
                        }

                        if (d.changelog && changelogBox && changelogText) {
                            changelogText.innerHTML = escapeHtml(d.changelog).replace(/\n/g, '<br>');
                            changelogBox.style.display = 'block';
                        }
                    } else {
                        if (statusEl) statusEl.textContent = (res.data && res.data.message) ? res.data.message : 'Помилка перевірки оновлення';
                    }
                })
                .catch(err => {
                    if (statusEl) statusEl.textContent = 'Помилка запиту до GitHub';
                });
        };

        window.runGitHubUpdate = function() {
            const statusEl = document.getElementById('update-status-msg');
            const btnRun = document.getElementById('btn-run-update');
            if (btnRun) btnRun.disabled = true;
            if (statusEl) statusEl.textContent = t('updating_in_progress');

            fetch(AJAX_URL + '?action=wood_calc_run_update&nonce=' + encodeURIComponent(NONCE), { method: 'POST' })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        if (statusEl) statusEl.textContent = t('update_success');
                        setTimeout(() => { location.reload(); }, 1500);
                    } else {
                        if (btnRun) btnRun.disabled = false;
                        if (statusEl) statusEl.textContent = (res.data && res.data.message) ? res.data.message : 'Помилка оновлення';
                    }
                })
                .catch(() => {
                    if (btnRun) btnRun.disabled = false;
                    if (statusEl) statusEl.textContent = 'Помилка оновлення плагіна';
                });
        };

        // Завантаження даних
        function loadData() {
            const statusEl = document.getElementById('wc-status');

            // 1. Спершу миттєво ініціалізуємося з серверного bootstrap (без затримки мережі)
            if (window.WOOD_CALC_BOOTSTRAP && window.WOOD_CALC_BOOTSTRAP.data) {
                const bData = window.WOOD_CALC_BOOTSTRAP.data;
                materials = Array.isArray(bData.materials) ? bData.materials : [];
                items = Array.isArray(bData.items) ? bData.items : [];
                if (bData.settings) {
                    if (bData.settings.lang && (bData.settings.lang === 'uk' || bData.settings.lang === 'en')) {
                        currentLang = bData.settings.lang;
                    }
                    if (bData.settings.accent_color && /^#[0-9a-fA-F]{6}$/.test(bData.settings.accent_color)) {
                        accentColor = bData.settings.accent_color;
                    }
                    if (typeof bData.settings.wipe_on_uninstall !== 'undefined') {
                        wipeOnUninstall = !!bData.settings.wipe_on_uninstall;
                    }
                    if (bData.settings.column_visibility && typeof bData.settings.column_visibility === 'object') {
                        columnVisibility = Object.assign({}, columnVisibility, bData.settings.column_visibility);
                    }
                }
                isDataInitialized = true;
                applyAccentColor();
                applyLanguageToDom();
                if (statusEl) statusEl.textContent = t('saved');
                return;
            }

            if (statusEl) statusEl.textContent = t('syncing');

            const savedLocalLang = localStorage.getItem(LANG_STORAGE_KEY);
            if (savedLocalLang === 'uk' || savedLocalLang === 'en') {
                currentLang = savedLocalLang;
            }

            fetch(AJAX_URL + '?action=wood_calc_get&nonce=' + encodeURIComponent(NONCE))
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data) {
                        materials = Array.isArray(res.data.materials) ? res.data.materials : [];
                        items = Array.isArray(res.data.items) ? res.data.items : [];
                        if (res.data.settings) {
                            if (res.data.settings.lang) {
                                currentLang = res.data.settings.lang;
                                localStorage.setItem(LANG_STORAGE_KEY, currentLang);
                                const langRadio = document.getElementById('wc-lang-' + currentLang);
                                if (langRadio) langRadio.checked = true;
                            }
                            if (res.data.settings.accent_color && /^#[0-9a-fA-F]{6}$/.test(res.data.settings.accent_color)) {
                                accentColor = res.data.settings.accent_color;
                            }
                            if (typeof res.data.settings.wipe_on_uninstall !== 'undefined') {
                                wipeOnUninstall = !!res.data.settings.wipe_on_uninstall;
                                const wipeCb = document.getElementById('wc-wipe-on-uninstall');
                                if (wipeCb) wipeCb.checked = wipeOnUninstall;
                            }
                            if (res.data.settings.column_visibility && typeof res.data.settings.column_visibility === 'object') {
                                columnVisibility = Object.assign({}, columnVisibility, res.data.settings.column_visibility);
                                ['photo', 'mat', 'dims', 'price', 'qty', 'sum'].forEach(col => {
                                    const cb = document.getElementById('col-toggle-' + col);
                                    if (cb && typeof columnVisibility[col] !== 'undefined') {
                                        cb.checked = !!columnVisibility[col];
                                    }
                                });
                                applyColumnVisibility();
                            }
                        }
                        applyAccentColor();
                        isDataInitialized = true;
                        if (statusEl) statusEl.textContent = t('saved');
                    } else {
                        fallbackToLocalStorage();
                    }
                    applyLanguageToDom();
                })
                .catch(() => {
                    fallbackToLocalStorage();
                    applyLanguageToDom();
                });
        }

        function fallbackToLocalStorage() {
            const statusEl = document.getElementById('wc-status');
            const local = localStorage.getItem(LOCAL_STORAGE_KEY);
            if (local) {
                try {
                    const parsed = JSON.parse(local);
                    if (parsed.materials && Array.isArray(parsed.materials)) materials = parsed.materials;
                    if (parsed.items && Array.isArray(parsed.items)) items = parsed.items;
                    if (parsed.settings && parsed.settings.lang) {
                        currentLang = parsed.settings.lang;
                        const langRadio = document.getElementById('wc-lang-' + currentLang);
                        if (langRadio) langRadio.checked = true;
                    }
                    if (parsed.settings && parsed.settings.accent_color) {
                        accentColor = parsed.settings.accent_color;
                    }
                    if (parsed.settings && typeof parsed.settings.wipe_on_uninstall !== 'undefined') {
                        wipeOnUninstall = !!parsed.settings.wipe_on_uninstall;
                        const wipeCb = document.getElementById('wc-wipe-on-uninstall');
                        if (wipeCb) wipeCb.checked = wipeOnUninstall;
                    }
                    if (parsed.settings && parsed.settings.column_visibility && typeof parsed.settings.column_visibility === 'object') {
                        columnVisibility = Object.assign({}, columnVisibility, parsed.settings.column_visibility);
                        ['photo', 'mat', 'dims', 'price', 'qty', 'sum'].forEach(col => {
                            const cb = document.getElementById('col-toggle-' + col);
                            if (cb && typeof columnVisibility[col] !== 'undefined') {
                                cb.checked = !!columnVisibility[col];
                            }
                        });
                        applyColumnVisibility();
                    }
                    applyAccentColor();
                    isDataInitialized = true;
                    if (statusEl) statusEl.textContent = t('offline');
                } catch(e) {}
            }
        }

        function saveData(silent) {
            if (!isDataInitialized) return;
            const statusEl = document.getElementById('wc-status');
            if (!silent && statusEl) statusEl.textContent = t('syncing');

            const payload = {
                materials: materials,
                items: items,
                settings: {
                    lang: currentLang,
                    accent_color: accentColor,
                    wipe_on_uninstall: wipeOnUninstall,
                    column_visibility: columnVisibility
                }
            };

            localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(payload));
            localStorage.setItem(LANG_STORAGE_KEY, currentLang);

            fetch(AJAX_URL + '?action=wood_calc_save&nonce=' + encodeURIComponent(NONCE), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success && statusEl) {
                    statusEl.textContent = t('saved');
                }
            })
            .catch(() => {
                isDataInitialized = true;
                    if (statusEl) statusEl.textContent = t('offline');
            });
        }

        window.downloadDataBackup = function() {
            const data = {
                materials: materials,
                items: items,
                settings: { lang: currentLang },
                export_date: new Date().toISOString()
            };
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'wood-calculator-backup-' + new Date().toISOString().slice(0, 10) + '.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        };

        window.runQuickCalc = function() {
            const matSel = document.getElementById('calc-mat');
            const len = parseFloat(document.getElementById('calc-len').value) || 0;
            const width = parseFloat(document.getElementById('calc-width').value) || 0;
            const rate = parseFloat(matSel ? matSel.value : 0) || 0;

            const areaCm2 = (len * width) / 100;
            const price = areaCm2 * rate;

            const resArea = document.getElementById('res-area');
            const resPrice = document.getElementById('res-price');
            if (resArea) resArea.textContent = areaCm2.toFixed(1) + ' ' + t('sq_cm');
            if (resPrice) resPrice.textContent = price.toFixed(2) + ' ' + t('curr');
        };

        window.sendToSaveForm = function() {
            const matSel = document.getElementById('calc-mat');
            const lenVal = document.getElementById('calc-len').value;
            const widthVal = document.getElementById('calc-width').value;
            const selectedOpt = matSel ? matSel.options[matSel.selectedIndex] : null;
            const matName = selectedOpt ? selectedOpt.getAttribute('data-name') : '';

            const len = parseFloat(lenVal) || 0;
            const width = parseFloat(widthVal) || 0;
            const rate = parseFloat(matSel ? matSel.value : 0) || 0;
            const price = ((len * width) / 100) * rate;

            const addName = document.getElementById('add-name');
            const addMat = document.getElementById('add-mat');
            const addLen = document.getElementById('add-len');
            const addWidth = document.getElementById('add-width');
            const addPrice = document.getElementById('add-price');

            if (addName) addName.value = (currentLang === 'uk' ? 'Виріб #' : 'Product #') + (items.length + 1);
            if (addLen) addLen.value = lenVal;
            if (addWidth) addWidth.value = widthVal;
            if (addPrice) addPrice.value = price.toFixed(2);

            if (addMat && matName) {
                for (let i = 0; i < addMat.options.length; i++) {
                    if (addMat.options[i].text === matName) {
                        addMat.selectedIndex = i;
                        break;
                    }
                }
            }
        };

        function renderMaterials() {
            const tbody = document.getElementById('materials-tbody');
            const calcMat = document.getElementById('calc-mat');
            const addMat = document.getElementById('add-mat');
            const editProdMat = document.getElementById('edit-prod-mat');

            if (tbody) {
                if (materials.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:16px;">' + t('no_materials') + '</td></tr>';
                } else {
                    tbody.innerHTML = materials.map((m, idx) => `
                        <tr>
                            <td><strong>${escapeHtml(m.name)}</strong></td>
                            <td>${parseFloat(m.price)}</td>
                            <td style="text-align:right;">
                                <button type="button" class="btn btn-outline btn-sm" onclick="openEditMaterialModal(${idx})" title="${t('modal_edit_mat_title')}">✏️</button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="askDeleteMaterial(${idx})" title="${t('btn_delete')}">🗑️</button>
                            </td>
                        </tr>
                    `).join('');
                }
            }

            const optionsHtml = materials.length === 0 
                ? `<option value="0">${t('no_materials')}</option>`
                : materials.map(m => `<option value="${m.price}" data-name="${escapeHtml(m.name)}">${escapeHtml(m.name)} (${parseFloat(m.price)} ${t('curr')}/${t('sq_cm')})</option>`).join('');

            if (calcMat) calcMat.innerHTML = optionsHtml;
            if (addMat) addMat.innerHTML = optionsHtml;
            if (editProdMat) editProdMat.innerHTML = optionsHtml;

            runQuickCalc();
        }

        window.addNewMaterial = function() {
            const nameInput = document.getElementById('mat-name');
            const priceInput = document.getElementById('mat-price');
            const name = (nameInput ? nameInput.value : '').trim();
            const price = parseFloat(priceInput ? priceInput.value : 0);

            if (!name || isNaN(price) || price <= 0) {
                showToast(t('enter_valid_mat'), 'error');
                return;
            }

            materials.push({
                id: 'm_' + Date.now(),
                name: name,
                price: price
            });

            if (nameInput) nameInput.value = '';
            if (priceInput) priceInput.value = '';

            renderMaterials();
            saveData();
        };

        window.openEditMaterialModal = function(idx) {
            const m = materials[idx];
            if (!m) return;
            document.getElementById('edit-mat-id').value = idx;
            document.getElementById('edit-mat-name').value = m.name;
            document.getElementById('edit-mat-price').value = m.price;
            document.getElementById('modal-edit-material').style.display = 'flex';
        };

        window.closeEditMaterialModal = function() {
            document.getElementById('modal-edit-material').style.display = 'none';
        };

        window.saveEditedMaterial = function() {
            const idx = parseInt(document.getElementById('edit-mat-id').value, 10);
            const name = document.getElementById('edit-mat-name').value.trim();
            const price = parseFloat(document.getElementById('edit-mat-price').value);

            if (!name || isNaN(price) || price <= 0) {
                showToast(t('enter_valid_mat'), 'error');
                return;
            }

            if (materials[idx]) {
                materials[idx].name = name;
                materials[idx].price = price;
            }

            closeEditMaterialModal();
            renderMaterials();
            saveData();
        };

        window.askDeleteMaterial = function(idx) {
            showConfirmModal(() => {
                materials.splice(idx, 1);
                renderMaterials();
                saveData();
            });
        };

        window.addCustomProduct = function() {
            const nameEl = document.getElementById('add-name');
            const matEl = document.getElementById('add-mat');
            const lenEl = document.getElementById('add-len');
            const widthEl = document.getElementById('add-width');
            const priceEl = document.getElementById('add-price');

            const name = (nameEl ? nameEl.value : '').trim();
            const selectedOpt = matEl ? matEl.options[matEl.selectedIndex] : null;
            const matName = selectedOpt ? selectedOpt.getAttribute('data-name') : '';
            const len = parseFloat(lenEl ? lenEl.value : 0) || 0;
            const width = parseFloat(widthEl ? widthEl.value : 0) || 0;
            const price = parseFloat(priceEl ? priceEl.value : 0) || 0;

            if (!name) {
                showToast(t('enter_valid_name'), 'error');
                return;
            }

            const newItem = {
                id: 'p_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
                name: name,
                material: matName || '-',
                len: len,
                width: width,
                price: price,
                qty: 1,
                selected: true,
                photo: ''
            };

            items.push(newItem);

            if (nameEl) nameEl.value = '';
            if (lenEl) lenEl.value = '';
            if (widthEl) widthEl.value = '';
            if (priceEl) priceEl.value = '';

            renderItems();
            saveData();
        };

        window.openEditProductModal = function(id) {
            const item = items.find(it => it.id === id);
            if (!item) return;

            document.getElementById('edit-prod-id').value = item.id;
            document.getElementById('edit-prod-name').value = item.name;
            document.getElementById('edit-prod-len').value = item.len;
            document.getElementById('edit-prod-width').value = item.width;
            document.getElementById('edit-prod-price').value = item.price;

            const editMatSel = document.getElementById('edit-prod-mat');
            if (editMatSel) {
                for (let i = 0; i < editMatSel.options.length; i++) {
                    if (editMatSel.options[i].getAttribute('data-name') === item.material) {
                        editMatSel.selectedIndex = i;
                        break;
                    }
                }
            }

            document.getElementById('modal-edit-product').style.display = 'flex';
        };

        window.closeEditProductModal = function() {
            document.getElementById('modal-edit-product').style.display = 'none';
        };

        window.saveEditedProduct = function() {
            const id = document.getElementById('edit-prod-id').value;
            const item = items.find(it => it.id === id);
            if (!item) return;

            const name = document.getElementById('edit-prod-name').value.trim();
            const editMatSel = document.getElementById('edit-prod-mat');
            const selectedOpt = editMatSel ? editMatSel.options[editMatSel.selectedIndex] : null;
            const matName = selectedOpt ? selectedOpt.getAttribute('data-name') : item.material;
            const len = parseFloat(document.getElementById('edit-prod-len').value) || 0;
            const width = parseFloat(document.getElementById('edit-prod-width').value) || 0;
            const price = parseFloat(document.getElementById('edit-prod-price').value) || 0;

            if (!name) {
                showToast(t('enter_valid_name'), 'error');
                return;
            }

            item.name = name;
            item.material = matName;
            item.len = len;
            item.width = width;
            item.price = price;

            closeEditProductModal();
            renderItems();
            saveData();
        };

        window.duplicateItem = function(id) {
            const item = items.find(it => String(it.id) === String(id));
            if (!item) return;

            const copy = JSON.parse(JSON.stringify(item));
            copy.id = 'p_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
            const suffix = (currentLang === 'uk' ? ' (копія)' : ' (copy)');
            copy.name = (copy.name || '') + suffix;

            const idx = items.findIndex(it => String(it.id) === String(id));
            if (idx !== -1) {
                items.splice(idx + 1, 0, copy);
            } else {
                items.push(copy);
            }

            renderItems();
            saveData(true);
            openEditProductModal(copy.id);
        };

        window.askDeleteItem = function(id) {
            showConfirmModal(() => {
                items = items.filter(it => it.id !== id);
                renderItems();
                saveData();
            });
        };

        let confirmCallback = null;
        function showConfirmModal(callback) {
            confirmCallback = callback;
            document.getElementById('modal-confirm-delete').style.display = 'flex';
        }
        window.closeConfirmModal = function() {
            document.getElementById('modal-confirm-delete').style.display = 'none';
            confirmCallback = null;
        };
        const confirmBtn = document.getElementById('confirm-del-btn');
        if (confirmBtn) {
            confirmBtn.onclick = function() {
                if (confirmCallback) confirmCallback();
                closeConfirmModal();
            };
        }

        function renderItems() {
            const tbody = document.getElementById('items-tbody');
            if (!tbody) return;

            const hideUnselected = document.getElementById('filter-selected')?.checked || false;
            let displayItems = items;
            if (hideUnselected) {
                displayItems = items.filter(it => it.selected);
            }

            if (displayItems.length === 0) {
                tbody.innerHTML = `<tr><td colspan="10" style="text-align:center; color:#94a3b8; padding:20px;">${t('no_items')}</td></tr>`;
                updateCalculations();
                return;
            }

            tbody.innerHTML = displayItems.map((item) => {
                const isSelected = item.selected !== false;
                const qty = item.qty || 1;
                const sum = (item.price * qty).toFixed(2);
                const dimsText = (item.len && item.width) ? `${item.len} × ${item.width} мм` : '-';

                return `
                    <tr id="row-${item.id}" data-id="${item.id}">
                        <td class="no-invoice" style="text-align:center;">
                            <span class="wc-drag-handle" title="Перетягнути для зміни порядку" draggable="true">⠿</span>
                        </td>
                        <td>
                            <input type="checkbox" ${isSelected ? 'checked' : ''} onchange="setItemSelected('${item.id}', this.checked)">
                        </td>
                        <td class="col-photo-cell">
                            ${item.photo ? `<img src="${escapeHtml(item.photo)}" style="width:36px; height:36px; object-fit:cover; border-radius:4px;">` : `<span style="color:#cbd5e1; font-size:16px;">🖼️</span>`}
                        </td>
                        <td>
                            <strong>${escapeHtml(item.name)}</strong>
                        </td>
                        <td class="col-mat-cell">${escapeHtml(item.material || '-')}</td>
                        <td class="col-dims-cell">${dimsText}</td>
                        <td class="col-price-cell">${parseFloat(item.price).toFixed(2)} ${t('curr')}</td>
                        <td class="col-qty-cell">
                            <input type="number" min="1" value="${qty}" style="width:65px; padding:4px 6px;" onchange="setItemQty('${item.id}', this.value)">
                        </td>
                        <td class="col-sum-cell" style="text-align:right; font-weight:700;">
                            ${sum} ${t('curr')}
                        </td>
                        <td class="no-invoice" style="text-align:right; white-space:nowrap;">
                            <button type="button" class="btn btn-outline btn-sm" onclick="duplicateItem('${item.id}')" title="Дублювати">📋</button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="openEditProductModal('${item.id}')" title="Редагувати">✏️</button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="askDeleteItem('${item.id}')" title="Видалити">🗑️</button>
                        </td>
                    </tr>
                `;
            }).join('');

            applyColumnVisibility();
            setupDragAndDrop();
            updateCalculations();
        }

        window.setItemSelected = function(id, selected) {
            const item = items.find(it => it.id === id);
            if (item) {
                item.selected = selected;
                saveData(true);
                updateCalculations();
            }
        };

        window.setItemQty = function(id, val) {
            const item = items.find(it => it.id === id);
            if (item) {
                item.qty = Math.max(1, parseInt(val, 10) || 1);
                saveData(true);
                renderItems();
            }
        };

        window.toggleSelectAll = function(selectAll) {
            items.forEach(it => it.selected = selectAll);
            const topCb = document.getElementById('select-all-top');
            if (topCb) topCb.checked = selectAll;
            saveData(true);
            renderItems();
        };

        function updateCalculations() {
            let selectedCount = 0;
            let totalQty = 0;
            let grandTotal = 0;

            items.forEach(item => {
                if (item.selected) {
                    selectedCount++;
                    const qty = item.qty || 1;
                    totalQty += qty;
                    grandTotal += (item.price * qty);
                }
            });

            const countEl = document.getElementById('sum-items-count');
            const qtyEl = document.getElementById('sum-total-qty');
            const sumEl = document.getElementById('sum-grand-total');

            if (countEl) countEl.textContent = selectedCount;
            if (qtyEl) qtyEl.textContent = totalQty;
            if (sumEl) sumEl.textContent = grandTotal.toFixed(2);
        }

        window.toggleColumnVisibility = function(colName, isVisible) {
            columnVisibility[colName] = isVisible;
            applyColumnVisibility();
            saveData(true);
        };

        function applyColumnVisibility() {
            const setDisplay = (selector, visible) => {
                document.querySelectorAll(selector).forEach(el => {
                    el.style.display = visible ? '' : 'none';
                });
            };

            setDisplay('.col-photo-header, .col-photo-cell', columnVisibility.photo);
            setDisplay('.col-mat-header, .col-mat-cell', columnVisibility.mat);
            setDisplay('.col-dims-header, .col-dims-cell', columnVisibility.dims);
            setDisplay('.col-price-header, .col-price-cell', columnVisibility.price);
            setDisplay('.col-qty-header, .col-qty-cell', columnVisibility.qty);
            setDisplay('.col-sum-header, .col-sum-cell', columnVisibility.sum);
        }

        window.enterInvoiceMode = function() {
            document.body.classList.add('invoice-mode');
            const dateEl = document.getElementById('inv-date');
            if (dateEl) {
                const now = new Date();
                dateEl.textContent = now.toLocaleDateString(currentLang === 'uk' ? 'uk-UA' : 'en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }
            const exitBtn = document.getElementById('exit-invoice-container');
            if (exitBtn) exitBtn.style.display = 'block';
        };

        window.exitInvoiceMode = function() {
            document.body.classList.remove('invoice-mode');
            const exitBtn = document.getElementById('exit-invoice-container');
            if (exitBtn) exitBtn.style.display = 'none';
        };

        
        let draggedRow = null;
        function setupDragAndDrop() {
            const tbody = document.getElementById('items-tbody') || document.getElementById('catalog-items-body');
            if (!tbody) return;

            const rows = tbody.querySelectorAll('tr[data-id]');
            rows.forEach(row => {
                const handle = row.querySelector('.wc-drag-handle');
                if (!handle) return;

                // Only allow dragging via the handle
                handle.onmousedown = () => {
                    row.draggable = true;
                };
                handle.onmouseup = () => {
                    row.draggable = false;
                };

                row.ondragstart = (e) => {
                    if (!row.draggable) {
                        e.preventDefault();
                        return;
                    }
                    draggedRow = row;
                    row.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', row.getAttribute('data-id') || '');
                };

                row.ondragend = () => {
                    row.draggable = false;
                    row.classList.remove('dragging');
                    rows.forEach(r => {
                        r.classList.remove('drop-above', 'drop-below');
                    });
                    draggedRow = null;
                };

                row.ondragover = (e) => {
                    e.preventDefault();
                    if (!draggedRow || draggedRow === row) return;
                    const rect = row.getBoundingClientRect();
                    const mid = rect.top + rect.height / 2;
                    row.classList.remove('drop-above', 'drop-below');
                    if (e.clientY < mid) {
                        row.classList.add('drop-above');
                    } else {
                        row.classList.add('drop-below');
                    }
                };

                row.ondragleave = (e) => {
                    row.classList.remove('drop-above', 'drop-below');
                };

                row.ondrop = (e) => {
                    e.preventDefault();
                    if (!draggedRow || draggedRow === row) return;

                    const srcId = draggedRow.getAttribute('data-id');
                    const targetId = row.getAttribute('data-id');
                    const isBelow = row.classList.contains('drop-below');

                    const srcIdx = items.findIndex(it => String(it.id) === String(srcId));
                    const targetIdx = items.findIndex(it => String(it.id) === String(targetId));

                    if (srcIdx !== -1 && targetIdx !== -1) {
                        const [moved] = items.splice(srcIdx, 1);
                        let insertAt = items.findIndex(it => String(it.id) === String(targetId));
                        if (isBelow) {
                            insertAt += 1;
                        }
                        items.splice(insertAt, 0, moved);
                        renderItems();
                        saveData();
                    }

                    row.classList.remove('drop-above', 'drop-below');
                };
            });
        }

function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const savedLocalLang = localStorage.getItem(LANG_STORAGE_KEY);
            if (savedLocalLang === 'uk' || savedLocalLang === 'en') {
                currentLang = savedLocalLang;
                const langRadio = document.getElementById('wc-lang-' + currentLang);
                if (langRadio) langRadio.checked = true;
            }
            try {
                const local = JSON.parse(localStorage.getItem(LOCAL_STORAGE_KEY) || '{}');
                if (local.settings && local.settings.accent_color && /^#[0-9a-fA-F]{6}$/.test(local.settings.accent_color)) {
                    accentColor = local.settings.accent_color;
                }
            } catch(e) {}
            applyAccentColor();
            loadData();
        });
        loadData();
    })();