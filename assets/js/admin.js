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
                tab_appearance: "Оформлення",
                tab_settings: "Налаштування",
                header_title: "🛠️ Розрахунок вартості виробів",
                btn_backup: "Завантажити бекап (JSON)",
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
                sec2_title: "Додати виріб у список",
                btn_add_product: "Додати виріб",
                lbl_add_name: "Назва виробу",
                lbl_add_mat: "Оберіть матеріал",
                lbl_add_price: "Ціна/шт (грн)",
                btn_add_save: "+ Зберегти",
                sec3_title: "3. Список виробів",
                btn_select_all: "Виділити всі",
                btn_deselect_all: "Зняти всі",
                inv_cols_title: "Колонки для накладної:",
                                col_material: "Матеріал",
                col_dims: "Розміри",
                col_price_pc: "Ціна / 1 шт",
                col_qty: "К-сть",
                col_sum: "Сума",
                col_actions: "Дії",
                summary_selected: "Обрано виробів:",
                summary_qty: "Загальна к-сть:",
                summary_sum: "Загальна сума:",
                pcs: "шт",
                curr: "грн",
                sq_cm: "см²",
                btn_invoice_mode: "📄 Переглянути накладну",
                modal_copy_prod_title: "Копіювання виробу",
                btn_back_to_calc: "← Повернутися до калькулятора",
                btn_exit_invoice: "← Повернутися до калькулятора",
                invoice_title: "РОЗРАХУНОК ЗАМОВЛЕННЯ",
                sec_materials_title: "📦 Довідник матеріалів",
                lbl_mat_name: "Назва матеріалу",
                lbl_mat_rate: "Тариф за 1 см² (грн)",
                btn_mat_add: "+ Додати",
                appearance_title: "🎨 Зовнішній вигляд",
                lbl_accent_color: "Акцентний колір кнопок та активних елементів",
                lbl_custom_color: "Довільний колір:",
                appearance_desc: "Обраний колір миттєво застосовується до кнопок, чекбоксів, підсвітки та рамок інтерфейсу без перезавантаження сторінки.",
                settings_title: "⚙️ Налаштування калькулятора",
                settings_lang: "Мова інтерфейсу",
                settings_lang_desc: "Обрана мова зберігається автоматично та використовується для калькулятора, каталогу і накладної.",
                backup_title: "💾 Резервне копіювання даних",
                backup_desc: "Збережіть повну резервну копію ваших матеріалів, виробів та налаштувань у файлі JSON для безпеки або перенесення.",
                lbl_wipe_uninstall: "Видаляти всі дані та налаштування при повному видаленні плагіна",
                desc_wipe_uninstall: "Якщо вимкнено — ваші створені матеріали, каталог виробів та налаштування збережуться навіть після деінсталяції плагіна.",
                updater_title: "🚀 Оновлення плагіна з GitHub",
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
                tab_catalog: "Всі вироби",
                catalog_title: "📋 Каталог усіх створених виробів",
                catalog_desc: "Тут зберігаються всі ваші створені вироби. Ви можете додавати їх у накладну, редагувати, дублювати або впорядковувати перетягуванням.",
                chk_add_to_invoice: "Додати в накладну",
                btn_add_from_catalog: "+ Додати з каталогу",
                btn_add_to_inv: "+ В накладну",
                in_invoice_badge: "В накладній",
                btn_remove_from_inv: "Прибрати з накладної",
                modal_catalog_title: "Додати вироби з каталогу в накладну",
                no_catalog_items: "Немає створених виробів у каталозі",
                no_invoice_items: "У накладній ще немає виробів. Додайте створений виріб або оберіть з вкладки «Всі вироби».",
                btn_download_png: "Завантажити PNG",
                btn_copy_png: "Скопіювати картинку",
                btn_download_pdf: "Завантажити PDF",
                btn_download_excel: "Завантажити Excel",
                btn_share_invoice: "Поділитися",
                btn_email_invoice: "Надіслати на Email",
                copied_image_success: "Зображення скопійовано в буфер обміну!",
                copied_image_failed: "Не вдалося скопіювати зображення.",
                all_items_in_invoice: "Усі вироби з каталогу вже є в накладній!",
                catalog_status_in_inv: "В накладній",
                catalog_status_not_in_inv: "Не в накладній",
                modal_del_title: "Підтвердження видалення",
                modal_del_text: "Ви дійсно бажаєте видалити цей елемент? Цю дію неможливо буде скасувати.",
                btn_cancel: "Скасувати",
                btn_save: "Зберегти зміни",
                btn_delete: "Видалити",
            modal_add_catalog_title: "Додати новий виріб у каталог",
            btn_create_in_catalog: "Зберегти в каталог",
            col_status: "Статус",
            err_invalid_dims: "Будь ласка, вкажіть коректні розміри (довжина та ширина мають бути більше 0).",
            err_invalid_price: "Ціна повинна бути 0 або більше.",
            err_enter_name: "Будь ласка, введіть назву виробу.",
            data_recovered_toast: "Дані успішно відновлено з локального дзеркала!",
            chk_modal_add_to_inv: "Додати також у поточну накладну",
            catalog_item_added: "Виріб успішно додано до каталогу!",
                no_items: "Немає створених виробів",
                no_materials: "Матеріали відсутні. Додайте перший матеріал у довіднику.",
                enter_valid_name: "Будь ласка, введіть коректну назву та розміри",
                enter_valid_mat: "Будь ласка, введіть назву матеріалу та коректний тариф",
            },
            en: {
                tab_calc: "Calculator",
                tab_appearance: "Appearance",
                tab_settings: "Settings",
                header_title: "🛠️ Product Cost Calculator",
                btn_backup: "Download Backup (JSON)",
                syncing: "● Syncing...",
                saved: "● Saved to WordPress",
                offline: "● Offline mode",
                sec1_title: "1. Quick Cost Calculator",
                lbl_calc_mat: "Material (rate per 1 cm²)",
                lbl_calc_len: "Length (mm)",
                lbl_calc_width: "Width (mm)",
                btn_send_to_form: "Add to Product Form ↓",
                res_area: "Calculated Area:",
                res_price: "Unit Price:",
                sec2_title: "Add Product to List",
                btn_add_product: "Add Product",
                lbl_add_name: "Product Name",
                lbl_add_mat: "Select Material",
                lbl_add_price: "Price/unit (UAH)",
                btn_add_save: "+ Save",
                sec3_title: "3. Product List",
                btn_select_all: "Select All",
                btn_deselect_all: "Deselect All",
                inv_cols_title: "Columns for Invoice:",
                                col_material: "Material",
                col_dims: "Dimensions",
                col_price_pc: "Price / 1 pc",
                col_qty: "Qty",
                col_sum: "Total",
                col_actions: "Actions",
                summary_selected: "Selected products:",
                summary_qty: "Total quantity:",
                summary_sum: "Grand total:",
                pcs: "pcs",
                curr: "UAH",
                sq_cm: "cm²",
                btn_invoice_mode: "📄 View Invoice",
                modal_copy_prod_title: "Copy Product",
                btn_back_to_calc: "← Back to Calculator",
                btn_exit_invoice: "← Back to Calculator",
                invoice_title: "ORDER ESTIMATE",
                sec_materials_title: "📦 Materials Directory",
                lbl_mat_name: "Material Name",
                lbl_mat_rate: "Rate per 1 cm² (UAH)",
                btn_mat_add: "+ Add",
                appearance_title: "🎨 Appearance",
                lbl_accent_color: "Accent color for buttons and active elements",
                lbl_custom_color: "Custom color:",
                appearance_desc: "The selected color is instantly applied to buttons, checkboxes, highlights, and borders in real time without refreshing.",
                settings_title: "⚙️ Calculator Settings",
                settings_lang: "Interface Language",
                settings_lang_desc: "The chosen language is saved automatically and applies to the calculator, catalog, and invoice.",
                backup_title: "💾 Data Backup",
                backup_desc: "Save a complete backup of your materials, items, and settings in JSON format for security or migration.",
                lbl_wipe_uninstall: "Delete all data and settings on full plugin uninstall",
                desc_wipe_uninstall: "When unchecked, your created materials, catalog items, and settings are preserved even after uninstalling the plugin.",
                updater_title: "🚀 GitHub Plugin Updater",
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
                tab_catalog: "All Products",
                catalog_title: "📋 All Created Products Catalog",
                catalog_desc: "All your created products are stored here. You can add them to the invoice, edit, duplicate or reorder by dragging.",
                chk_add_to_invoice: "Add to invoice",
                btn_add_from_catalog: "+ Add from catalog",
                btn_add_to_inv: "+ To invoice",
                in_invoice_badge: "In invoice",
                btn_remove_from_inv: "Remove from invoice",
                modal_catalog_title: "Add Products from Catalog to Invoice",
                no_catalog_items: "No products created in catalog yet",
                no_invoice_items: "No items in the invoice yet. Add a created product or choose from the All Products tab.",
                btn_download_png: "Download PNG",
                btn_copy_png: "Copy Image",
                btn_download_pdf: "Download PDF",
                btn_download_excel: "Download Excel",
                btn_share_invoice: "Share",
                btn_email_invoice: "Send via Email",
                copied_image_success: "Image copied to clipboard!",
                copied_image_failed: "Failed to copy image.",
                all_items_in_invoice: "All products from catalog are already in the invoice!",
                catalog_status_in_inv: "In invoice",
                catalog_status_not_in_inv: "Not in invoice",

                modal_del_title: "Confirm Deletion",
                modal_del_text: "Are you sure you want to delete this item? This action cannot be undone.",
                btn_cancel: "Cancel",
                btn_save: "Save Changes",
                btn_delete: "Delete",
            modal_add_catalog_title: "Add New Product to Catalog",
            btn_create_in_catalog: "Save to Catalog",
            col_status: "Status",
            err_invalid_dims: "Please enter valid dimensions (length and width must be greater than 0).",
            err_invalid_price: "Price must be 0 or greater.",
            err_enter_name: "Please enter product name.",
            data_recovered_toast: "Data successfully recovered from local mirror!",
            chk_modal_add_to_inv: "Also add to current invoice",
            catalog_item_added: "Product added to catalog successfully!",
                no_items: "No products created yet",
                no_materials: "No materials added yet. Please add a material in directory.",
                enter_valid_name: "Please enter valid name and dimensions",
                enter_valid_mat: "Please enter material name and valid rate",
            }
        };

        let currentLang = 'uk';
        let materials = [];
        let items = [];
        let columnVisibility = {
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
                catalog: document.getElementById('wc-tab-pane-catalog'),
                appearance: document.getElementById('wc-tab-pane-appearance'),
                settings: document.getElementById('wc-tab-pane-settings')
            };
            const buttons = {
                calc: document.getElementById('tab-nav-calc'),
                catalog: document.getElementById('tab-nav-catalog'),
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

            if (tabName === 'catalog') {
                renderCatalogTab();
            } else if (tabName === 'appearance') {
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
                const bMats = Array.isArray(bData.materials) ? bData.materials : [];
                const bItems = Array.isArray(bData.items) ? bData.items : [];
                const hasServerData = bMats.length > 0 || bItems.length > 0 || !!bData.initialized;

                if (!hasServerData) {
                    const localRaw = localStorage.getItem(LOCAL_STORAGE_KEY);
                    if (localRaw) {
                        try {
                            const parsed = JSON.parse(localRaw);
                            if ((Array.isArray(parsed.materials) && parsed.materials.length > 0) || (Array.isArray(parsed.items) && parsed.items.length > 0)) {
                                materials = Array.isArray(parsed.materials) ? parsed.materials : [];
                                items = Array.isArray(parsed.items) ? parsed.items : [];
                                if (parsed.settings) {
                                    if (parsed.settings.lang && (parsed.settings.lang === 'uk' || parsed.settings.lang === 'en')) {
                                        currentLang = parsed.settings.lang;
                                    }
                                    if (parsed.settings.accent_color && /^#[0-9a-fA-F]{6}$/.test(parsed.settings.accent_color)) {
                                        accentColor = parsed.settings.accent_color;
                                    }
                                    if (typeof parsed.settings.wipe_on_uninstall !== 'undefined') {
                                        wipeOnUninstall = !!parsed.settings.wipe_on_uninstall;
                                    }
                                    if (parsed.settings.column_visibility && typeof parsed.settings.column_visibility === 'object') {
                                        columnVisibility = Object.assign({}, columnVisibility, parsed.settings.column_visibility);
                                    }
                                }
                                isDataInitialized = true;
                                applyAccentColor();
                                applyLanguageToDom();
                                showToast(t('data_recovered_toast'));
                                saveData(true);
                                if (statusEl) statusEl.textContent = t('saved');
                                return;
                            }
                        } catch (e) {}
                    }
                }

                materials = bMats;
                items = bItems;
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
                                ['mat', 'dims', 'price', 'qty', 'sum'].forEach(col => {
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
                        ['mat', 'dims', 'price', 'qty', 'sum'].forEach(col => {
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

        let saveDebounceTimer = null;
        let saveSequenceId = 0;
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

            // Synchronously mirror immediately to LocalStorage (zero lag, reliable mirror)
            localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(payload));
            localStorage.setItem(LANG_STORAGE_KEY, currentLang);

            // Debounce server AJAX requests by 250ms to prevent spamming database on rapid clicks/input
            if (saveDebounceTimer) {
                clearTimeout(saveDebounceTimer);
            }
            saveDebounceTimer = setTimeout(function() {
                const currentSeq = ++saveSequenceId;
                fetch(AJAX_URL + '?action=wood_calc_save&nonce=' + encodeURIComponent(NONCE), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(r => r.json())
                .then(res => {
                    if (currentSeq !== saveSequenceId) return;
                    if (res.success && statusEl) {
                        statusEl.textContent = t('saved');
                    }
                })
                .catch(() => {
                    if (currentSeq !== saveSequenceId) return;
                    isDataInitialized = true;
                    if (statusEl) statusEl.textContent = t('offline');
                });
            }, 250);
        }

        window.downloadDataBackup = function() {
            const data = {
                materials: materials,
                items: items,
                settings: {
                    lang: currentLang,
                    accent_color: accentColor,
                    wipe_on_uninstall: wipeOnUninstall,
                    column_visibility: columnVisibility
                },
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
                                <button type="button" class="btn-delete-transp" onclick="askDeleteMaterial(${idx})" title="${t('btn_delete')}">🗑️</button>
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
                if (nameEl) nameEl.focus();
                return;
            }
            if (len <= 0 || width <= 0) {
                showToast(t('err_invalid_dims'), 'error');
                return;
            }
            if (isNaN(price) || price < 0) {
                showToast(t('err_invalid_price'), 'error');
                return;
            }

            const addInvChk = document.getElementById('add-to-invoice-chk');
            const inInvoiceVal = addInvChk ? addInvChk.checked : true;

            const newItem = {
                id: 'p_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
                name: name,
                material: matName || '-',
                len: len,
                width: width,
                price: price,
                qty: 1,
                selected: true,
                in_invoice: inInvoiceVal,
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
            const item = items.find(it => String(it.id) === String(id));
            if (!item) return;

            const titleEl = document.getElementById('modal-edit-prod-title');
            if (titleEl) titleEl.textContent = t('modal_edit_prod_title');

            document.getElementById('edit-prod-id').value = item.id;
            let srcIdEl = document.getElementById('edit-prod-source-id');
            if (srcIdEl) srcIdEl.value = '';
            let actionEl = document.getElementById('edit-prod-action');
            if (actionEl) actionEl.value = 'edit';

            document.getElementById('edit-prod-name').value = item.name || '';
            document.getElementById('edit-prod-len').value = item.len || '';
            document.getElementById('edit-prod-width').value = item.width || '';
            document.getElementById('edit-prod-price').value = item.price || '';

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

        window.openCopyProductModal = function(id, target) {
            const item = items.find(it => String(it.id) === String(id));
            if (!item) return;

            const titleEl = document.getElementById('modal-edit-prod-title');
            if (titleEl) titleEl.textContent = t('modal_copy_prod_title');

            document.getElementById('edit-prod-id').value = '';
            let srcIdEl = document.getElementById('edit-prod-source-id');
            if (srcIdEl) srcIdEl.value = item.id;
            let actionEl = document.getElementById('edit-prod-action');
            if (actionEl) actionEl.value = 'copy';
            let targetEl = document.getElementById('edit-prod-target');
            if (targetEl) targetEl.value = target || 'invoice';

            const copySuffix = (currentLang === 'uk' ? ' (копія)' : ' (copy)');
            document.getElementById('edit-prod-name').value = (item.name || '') + copySuffix;
            document.getElementById('edit-prod-len').value = item.len || '';
            document.getElementById('edit-prod-width').value = item.width || '';
            document.getElementById('edit-prod-price').value = item.price || '';

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
            document.getElementById('edit-prod-id').value = '';
            const srcIdEl = document.getElementById('edit-prod-source-id');
            if (srcIdEl) srcIdEl.value = '';
            const actionEl = document.getElementById('edit-prod-action');
            if (actionEl) actionEl.value = 'edit';
        };

        window.saveEditedProduct = function() {
            const id = document.getElementById('edit-prod-id').value;
            const srcIdEl = document.getElementById('edit-prod-source-id');
            const sourceId = srcIdEl ? srcIdEl.value : '';
            const actionEl = document.getElementById('edit-prod-action');
            const action = actionEl ? actionEl.value : (id ? 'edit' : 'copy');
            const targetEl = document.getElementById('edit-prod-target');
            const target = targetEl ? targetEl.value : 'invoice';

            const name = document.getElementById('edit-prod-name').value.trim();
            const editMatSel = document.getElementById('edit-prod-mat');
            const selectedOpt = editMatSel ? editMatSel.options[editMatSel.selectedIndex] : null;
            const matName = selectedOpt ? selectedOpt.getAttribute('data-name') : '';
            const len = parseFloat(document.getElementById('edit-prod-len').value) || 0;
            const width = parseFloat(document.getElementById('edit-prod-width').value) || 0;
            const price = parseFloat(document.getElementById('edit-prod-price').value) || 0;

            if (!name) {
                showToast(t('enter_valid_name'), 'error');
                return;
            }

            if (action === 'copy' && sourceId) {
                const orig = items.find(it => String(it.id) === String(sourceId));
                const newId = 'p_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
                const isForInvoice = (target === 'invoice');
                const newProduct = {
                    id: newId,
                    name: name,
                    material: matName || (orig ? orig.material : ''),
                    len: len,
                    width: width,
                    price: price,
                    qty: (isForInvoice && orig) ? (orig.qty || 1) : 1,
                    selected: isForInvoice,
                    in_invoice: isForInvoice,
                    photo: orig ? (orig.photo || '') : ''
                };

                const srcIdx = items.findIndex(it => String(it.id) === String(sourceId));
                if (srcIdx !== -1) {
                    items.splice(srcIdx + 1, 0, newProduct);
                } else {
                    items.push(newProduct);
                }

                closeEditProductModal();
                saveData();
                renderItems();
                renderCatalogTab();
                showToast(isForInvoice ? (currentLang === 'uk' ? 'Копію створено в накладній' : 'Copy created in invoice') : (currentLang === 'uk' ? 'Копію створено в каталозі' : 'Copy created in catalog'), 'success');
                return;
            } else if (id) {
                const item = items.find(it => String(it.id) === String(id));
                if (!item) return;
                item.name = name;
                item.material = matName || item.material;
                item.len = len;
                item.width = width;
                item.price = price;

                closeEditProductModal();
                saveData();
                renderItems();
                renderCatalogTab();
                showToast(currentLang === 'uk' ? 'Виріб успішно оновлено' : 'Product updated', 'success');
                return;
            }

            closeEditProductModal();
        };

        window.duplicateItem = function(id) {
            const item = items.find(it => String(it.id) === String(id));
            if (!item) return;

            // Prepare modal for copying, DO NOT add to items yet!
            document.getElementById('edit-prod-id').value = '';
            let srcIdEl = document.getElementById('edit-prod-source-id');
            if (!srcIdEl) {
                srcIdEl = document.createElement('input');
                srcIdEl.type = 'hidden';
                srcIdEl.id = 'edit-prod-source-id';
                document.getElementById('modal-edit-product').appendChild(srcIdEl);
            }
            srcIdEl.value = item.id;

            const suffix = (currentLang === 'uk' ? ' (копія)' : ' (copy)');
            document.getElementById('edit-prod-name').value = (item.name || '') + suffix;
            document.getElementById('edit-prod-len').value = item.len || '';
            document.getElementById('edit-prod-width').value = item.width || '';
            document.getElementById('edit-prod-price').value = item.price || '';

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

        
        
        // Дії для накладної (Invoice actions)
        window.duplicateInvoiceItem = function(id) {
            const orig = items.find(it => String(it.id) === String(id));
            if (!orig) return;
            const copyName = orig.name ? (orig.name + (currentLang === 'uk' ? ' (копія)' : ' (copy)')) : (currentLang === 'uk' ? 'Копія виробу' : 'Copy of product');
            const newItem = {
                ...orig,
                id: Date.now() + Math.floor(Math.random() * 1000),
                name: copyName,
                in_invoice: true,
                selected: true
            };
            items.push(newItem);
            saveData();
            renderItems();
            renderCatalogTab();
            showToast(currentLang === 'uk' ? 'Копію створено в накладній' : 'Copy created in invoice', 'success');
        };

        // Дії для каталогу (Catalog actions)
        window.duplicateCatalogItem = function(id) {
            const orig = items.find(it => String(it.id) === String(id));
            if (!orig) return;
            const copyName = orig.name ? (orig.name + (currentLang === 'uk' ? ' (копія)' : ' (copy)')) : (currentLang === 'uk' ? 'Копія виробу' : 'Copy of product');
            const newItem = {
                ...orig,
                id: Date.now() + Math.floor(Math.random() * 1000),
                name: copyName,
                in_invoice: false,
                selected: false
            };
            items.push(newItem);
            saveData();
            renderCatalogTab();
            renderItems();
            showToast(currentLang === 'uk' ? 'Копію створено в каталозі' : 'Copy created in catalog', 'success');
        };

        window.askDeleteCatalogItem = function(id) {
            showConfirmModal(() => {
                items = items.filter(it => String(it.id) !== String(id));
                saveData();
                renderCatalogTab();
                renderItems();
                showToast(currentLang === 'uk' ? 'Виріб видалено з каталогу' : 'Item deleted from catalog', 'success');
            });
        };

        // Invoice toggle and catalog helpers
        window.addToInvoice = function(id) {
            const it = items.find(x => String(x.id) === String(id));
            if (it) {
                it.in_invoice = true;
                it.selected = true;
                renderItems();
                renderCatalogTab();
                saveData();
                showToast(currentLang === 'uk' ? 'Виріб додано до накладної' : 'Item added to invoice', 'success');
            }
        };

        
        window.askRemoveFromInvoice = function(id) {
            showConfirmModal(() => {
                removeFromInvoice(id);
            });
        };

        window.removeFromInvoice = function(id) {
            const it = items.find(x => String(x.id) === String(id));
            if (it) {
                it.in_invoice = false;
                renderItems();
                renderCatalogTab();
                saveData();
                showToast(currentLang === 'uk' ? 'Виріб вилучено з накладної' : 'Item removed from invoice', 'info');
            }
        };

                window.openAddFromCatalogModal = function() {
            const modal = document.getElementById('modal-catalog-picker');
            const container = document.getElementById('catalog-picker-list');
            if (!modal || !container) return;

            if (items.length === 0) {
                container.innerHTML = '<div style="text-align:center; padding:24px; color:#64748b;">' + t('no_catalog_items') + '</div>';
            } else {
                container.innerHTML = '<div style="display:flex; flex-direction:column; gap:8px;">' +
                    items.map(it => {
                        const isInInv = (it.in_invoice !== false);
                        const q = it.qty || 1;
                        return '<div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;">' +
                            '<div>' +
                                '<div style="display:flex; align-items:center; gap:8px;">' +
                                    '<strong style="color:var(--tc-dark); font-size:14px;">' + escapeHtml(it.name) + '</strong>' +
                                    (isInInv ? '<span style="font-size:11px; font-weight:600; padding:2px 6px; background:#dcfce7; color:#166534; border-radius:4px;">✓ ' + t('in_invoice_badge') + ' (' + q + ' шт)</span>' : '') +
                                '</div>' +
                                '<div style="font-size:12px; color:#64748b; margin-top:3px;">' +
                                    (it.material ? escapeHtml(it.material) + ' · ' : '') +
                                    ((it.len && it.width) ? it.len + '×' + it.width + ' мм · ' : '') +
                                    parseFloat(it.price || 0).toFixed(2) + ' ' + t('curr') +
                                '</div>' +
                            '</div>' +
                            '<div style="display:flex; gap:6px;">' +
                                (isInInv ?
                                    '<button type="button" class="btn btn-outline btn-sm" onclick="setItemQty(\' + it.id + \', ' + (q + 1) + '); openAddFromCatalogModal();" title="Збільшити кількість">+ 1 шт</button>' :
                                    '<button type="button" class="btn btn-green btn-sm" onclick="addToInvoice(\'' + it.id + '\'); openAddFromCatalogModal();">' + t('btn_add_to_inv') + '</button>'
                                ) +
                            '</div>' +
                        '</div>';
                    }).join('') +
                '</div>';
            }

            modal.style.display = 'flex';
        };

        window.closeAddFromCatalogModal = function() {
            const modal = document.getElementById('modal-catalog-picker');
            if (modal) modal.style.display = 'none';
        };

        window.renderCatalogTab = function() {
            const tbody = document.getElementById('catalog-tab-tbody');
            if (!tbody) return;

            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:#94a3b8; padding:30px;">${t('no_catalog_items')}</td></tr>`;
                return;
            }

            tbody.innerHTML = items.map((item) => {
                const dimsText = (item.len && item.width) ? `${item.len} × ${item.width} мм` : '-';
                const isInInv = (item.in_invoice !== false);

                return `
                    <tr id="catalog-row-${item.id}" data-id="${item.id}">
                        <td style="text-align:center;">
                            <span class="wc-drag-handle" title="Перетягнути для зміни порядку">⠿</span>
                        </td>
                        <td>
                            <strong>${escapeHtml(item.name)}</strong>
                        </td>
                        <td>${escapeHtml(item.material || '-')}</td>
                        <td>${dimsText}</td>
                        <td style="text-align:right; font-weight:600;">${parseFloat(item.price || 0).toFixed(2)} ${t('curr')}</td>
                        <td style="text-align:center;">
                            <div style="display:inline-flex; gap:6px; align-items:center;">
                                ${isInInv ? `
                                    <button type="button" class="btn btn-outline btn-sm" style="color:var(--tc-green); border-color:var(--tc-green);" onclick="removeFromInvoice('${item.id}')" title="${t('btn_remove_from_inv')}">
                                        ✓ ${t('in_invoice_badge')}
                                    </button>
                                ` : `
                                    <button type="button" class="btn btn-green btn-sm" onclick="addToInvoice('${item.id}')">
                                        ${t('btn_add_to_inv')}
                                    </button>
                                `}
                                <button type="button" class="btn btn-outline btn-sm" onclick="openCopyProductModal('${item.id}', 'catalog')" title="Дублювати в каталозі">📋</button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="openEditProductModal('${item.id}')" title="Редагувати">✏️</button>
                                <button type="button" class="btn-delete-transp" onclick="askDeleteCatalogItem('${item.id}')" title="Видалити">🗑️</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            setupUnifiedTableDnD('catalog-tab-tbody', (srcId, targetId, isBelow) => {
                const srcIdx = items.findIndex(it => String(it.id) === String(srcId));
                const targetIdx = items.findIndex(it => String(it.id) === String(targetId));
                if (srcIdx !== -1 && targetIdx !== -1) {
                    const [moved] = items.splice(srcIdx, 1);
                    let insertAt = items.findIndex(it => String(it.id) === String(targetId));
                    if (isBelow) insertAt += 1;
                    items.splice(insertAt, 0, moved);
                    renderCatalogTab();
                    renderItems();
                    saveData();
                }
            });
        };

        function renderItems() {
            const tbody = document.getElementById('items-tbody');
            if (!tbody) return;

            const isInvoiceMode = document.body.classList.contains('invoice-mode');
                        // Invoice items: only items where in_invoice is true (default true)
            let invoiceItems = items.filter(it => it.in_invoice !== false);
            let displayItems = invoiceItems;
            if (isInvoiceMode) {
                // In invoice mode: only show selected (checked) items
                displayItems = invoiceItems.filter(it => it.selected === true);
            }

            if (displayItems.length === 0) {
                tbody.innerHTML = `<tr><td colspan="10" style="text-align:center; color:#94a3b8; padding:24px;">${invoiceItems.length === 0 ? t('no_invoice_items') : t('no_items')}</td></tr>`;
                updateCalculations();
                return;
            }

            tbody.innerHTML = displayItems.map((item) => {
                const isSelected = item.selected === true;
                const qty = item.qty || 1;
                const sum = (item.price * qty).toFixed(2);
                const dimsText = (item.len && item.width) ? `${item.len} × ${item.width} мм` : '-';

                return `
                    <tr id="row-${item.id}" data-id="${item.id}">
                        <td class="no-invoice" style="text-align:center;">
                            <span class="wc-drag-handle" title="Перетягнути для зміни порядку">⠿</span>
                        </td>
                        <td class="no-invoice">
                            <input type="checkbox" ${isSelected ? 'checked' : ''} onchange="setItemSelected('${item.id}', this.checked)">
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
                            <button type="button" class="btn btn-outline btn-sm" onclick="openCopyProductModal('${item.id}', 'invoice')" title="Дублювати в накладній">📋</button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="openEditProductModal('${item.id}')" title="Редагувати">✏️</button>
                            <button type="button" class="btn-delete-transp" onclick="askRemoveFromInvoice('${item.id}')" title="Вилучити з накладної">🗑️</button>
                        </td>
                    </tr>
                `;
            }).join('');

            applyColumnVisibility();
            setupUnifiedTableDnD('items-tbody', (srcId, targetId, isBelow) => {
                const srcIdx = items.findIndex(it => String(it.id) === String(srcId));
                const targetIdx = items.findIndex(it => String(it.id) === String(targetId));
                if (srcIdx !== -1 && targetIdx !== -1) {
                    const [moved] = items.splice(srcIdx, 1);
                    let insertAt = items.findIndex(it => String(it.id) === String(targetId));
                    if (isBelow) insertAt += 1;
                    items.splice(insertAt, 0, moved);
                    renderItems();
                    renderCatalogTab();
                    saveData();
                }
            });
            updateCalculations();
        }

        window.setItemSelected = function(id, selected) {
            const item = items.find(it => String(it.id) === String(id));
            if (item) {
                item.selected = Boolean(selected);
                saveData(true);
                updateCalculations();
            }
        };

        window.setItemQty = function(id, val) {
            const item = items.find(it => String(it.id) === String(id));
            if (item) {
                item.qty = Math.max(1, parseInt(val, 10) || 1);
                saveData(true);
                renderItems();
            }
        };

        window.toggleSelectAll = function(selectAll) {
            const flag = Boolean(selectAll);
            items.forEach(it => {
                if (it.in_invoice !== false) {
                    it.selected = flag;
                }
            });
            const topCb = document.getElementById('select-all-top');
            if (topCb) topCb.checked = flag;
            saveData(true);
            renderItems();
        };

        function updateCalculations() {
            let selectedCount = 0;
            let totalQty = 0;
            let grandTotal = 0;

            const invoiceItems = items.filter(it => it.in_invoice !== false);
            invoiceItems.forEach(item => {
                if (item.selected === true) {
                    selectedCount++;
                    const qty = parseInt(item.qty, 10) || 1;
                    totalQty += qty;
                    grandTotal += ((parseFloat(item.price) || 0) * qty);
                }
            });

            const countEl = document.getElementById('sum-items-count');
            const qtyEl = document.getElementById('sum-total-qty');
            const sumEl = document.getElementById('sum-grand-total');

            if (countEl) countEl.textContent = selectedCount;
            if (qtyEl) qtyEl.textContent = totalQty;
            if (sumEl) sumEl.textContent = grandTotal.toFixed(2);

            // Invoice view button: disabled when no items selected
            const invBtn = document.getElementById('btn-invoice-mode');
            if (invBtn) {
                if (selectedCount === 0) {
                    invBtn.disabled = true;
                    invBtn.style.opacity = '0.45';
                    invBtn.style.cursor = 'not-allowed';
                    invBtn.style.pointerEvents = 'none';
                } else {
                    invBtn.disabled = false;
                    invBtn.style.opacity = '1';
                    invBtn.style.cursor = 'pointer';
                    invBtn.style.pointerEvents = 'auto';
                }
            }

            // Sync top select-all checkbox
            const topCb = document.getElementById('select-all-top');
            if (topCb) {
                topCb.checked = invoiceItems.length > 0 && selectedCount === invoiceItems.length;
            }
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

                        setDisplay('.col-mat-header, .col-mat-cell', columnVisibility.mat);
            setDisplay('.col-dims-header, .col-dims-cell', columnVisibility.dims);
            setDisplay('.col-price-header, .col-price-cell', columnVisibility.price);
            setDisplay('.col-qty-header, .col-qty-cell', columnVisibility.qty);
            setDisplay('.col-sum-header, .col-sum-cell', columnVisibility.sum);
        }

        window.enterInvoiceMode = function() {
            const invoiceItems = items.filter(it => it.in_invoice !== false && it.selected === true);
            if (invoiceItems.length === 0) {
                showToast(currentLang === 'uk' ? 'Оберіть хоча б один виріб для перегляду накладної' : 'Select at least one product to view invoice', 'error');
                return;
            }
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
            renderItems();
            const exitBtn = document.getElementById('exit-invoice-container');
            if (exitBtn) exitBtn.style.display = 'block';
        };

        window.exitInvoiceMode = function() {
            document.body.classList.remove('invoice-mode');
            renderItems();
            const exitBtn = document.getElementById('exit-invoice-container');
            if (exitBtn) exitBtn.style.display = 'none';
        };

        
        
        // --- UNIFIED DRAG AND DROP (MOUSE + MOBILE TOUCH) ---
        function setupUnifiedTableDnD(tbodyId, onReorder) {
            const tbody = document.getElementById(tbodyId);
            if (!tbody) return;

            const rows = Array.from(tbody.querySelectorAll('tr[data-id]'));
            let draggedRow = null;
            let touchTargetRow = null;
            let touchInsertBelow = false;

            rows.forEach(row => {
                const handle = row.querySelector('.wc-drag-handle');
                if (!handle) return;

                // Mouse Drag-and-Drop
                handle.onmousedown = () => { row.draggable = true; };
                handle.onmouseup = () => { row.draggable = false; };

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
                    rows.forEach(r => r.classList.remove('drop-above', 'drop-below'));
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

                row.ondragleave = () => {
                    row.classList.remove('drop-above', 'drop-below');
                };

                row.ondrop = (e) => {
                    e.preventDefault();
                    if (!draggedRow || draggedRow === row) return;
                    const srcId = draggedRow.getAttribute('data-id');
                    const targetId = row.getAttribute('data-id');
                    const isBelow = row.classList.contains('drop-below');
                    row.classList.remove('drop-above', 'drop-below');
                    if (srcId && targetId && onReorder) {
                        onReorder(srcId, targetId, isBelow);
                    }
                };

                // Mobile Touch Drag-and-Drop on Handle
                handle.ontouchstart = (e) => {
                    if (e.touches.length !== 1) return;
                    draggedRow = row;
                    row.classList.add('dragging');
                    touchTargetRow = null;
                };

                handle.ontouchmove = (e) => {
                    if (!draggedRow || e.touches.length !== 1) return;
                    e.preventDefault();
                    const touch = e.touches[0];
                    const elUnder = document.elementFromPoint(touch.clientX, touch.clientY);
                    if (!elUnder) return;

                    const target = elUnder.closest('tr[data-id]');
                    rows.forEach(r => r.classList.remove('drop-above', 'drop-below'));

                    if (target && target !== draggedRow && target.closest('tbody') === tbody) {
                        touchTargetRow = target;
                        const rect = target.getBoundingClientRect();
                        const mid = rect.top + rect.height / 2;
                        touchInsertBelow = (touch.clientY >= mid);
                        target.classList.add(touchInsertBelow ? 'drop-below' : 'drop-above');
                    } else {
                        touchTargetRow = null;
                    }
                };

                handle.ontouchend = () => {
                    if (!draggedRow) return;
                    draggedRow.classList.remove('dragging');
                    rows.forEach(r => r.classList.remove('drop-above', 'drop-below'));

                    if (touchTargetRow && draggedRow !== touchTargetRow) {
                        const srcId = draggedRow.getAttribute('data-id');
                        const targetId = touchTargetRow.getAttribute('data-id');
                        if (srcId && targetId && onReorder) {
                            onReorder(srcId, targetId, touchInsertBelow);
                        }
                    }
                    draggedRow = null;
                    touchTargetRow = null;
                };

                handle.ontouchcancel = () => {
                    if (draggedRow) draggedRow.classList.remove('dragging');
                    rows.forEach(r => r.classList.remove('drop-above', 'drop-below'));
                    draggedRow = null;
                    touchTargetRow = null;
                };
            });
        }

        // --- EXPORT & SHARE FUNCTIONS (100% COMPLETE & WORKING) ---
        function getInvoiceDataForExport() {
            const invoiceItems = items.filter(it => it.in_invoice !== false && it.selected === true);
            const now = new Date();
            const dateStr = now.toLocaleDateString(currentLang === 'uk' ? 'uk-UA' : 'en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            let totalQty = 0;
            let grandTotal = 0;
            invoiceItems.forEach(it => {
                const q = it.qty || 1;
                totalQty += q;
                grandTotal += (parseFloat(it.price || 0) * q);
            });
            return { items: invoiceItems, dateStr, totalQty, grandTotal };
        }

        function createInvoiceCanvasBlob() {
            return new Promise((resolve) => {
                const { items: invItems, dateStr, totalQty, grandTotal } = getInvoiceDataForExport();
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const scale = 2; // High-res Retina

                const width = 840;
                const headerHeight = 110;
                const tableHeaderSpacing = 54;
                const rowHeight = 44;
                const footerBoxHeight = 66;
                const bottomPadding = 45;
                const rowsCount = Math.max(1, invItems.length);
                const height = headerHeight + tableHeaderSpacing + (rowsCount * rowHeight) + footerBoxHeight + bottomPadding;

                canvas.width = width * scale;
                canvas.height = height * scale;
                ctx.scale(scale, scale);

                // Background
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, width, height);

                // Header Dark Graphite
                ctx.fillStyle = '#24272a';
                ctx.fillRect(0, 0, width, headerHeight);

                // Header Title
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 22px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
                ctx.fillText(currentLang === 'uk' ? 'РОЗРАХУНОК ЗАМОВЛЕННЯ' : 'ORDER CALCULATION', 36, 48);

                // Header Date
                ctx.fillStyle = '#cbd5e1';
                ctx.font = '14px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
                ctx.fillText(dateStr, 36, 78);

                // Accent Line
                ctx.fillStyle = accentColor || '#95b504';
                ctx.fillRect(0, headerHeight - 4, width, 4);

                // Table Header
                let y = headerHeight + 30;
                ctx.fillStyle = '#64748b';
                ctx.font = 'bold 12px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
                ctx.fillText('№', 36, y);
                ctx.fillText(currentLang === 'uk' ? 'НАЗВА ВИРОБУ' : 'PRODUCT NAME', 80, y);
                ctx.fillText(currentLang === 'uk' ? 'МАТЕРІАЛ' : 'MATERIAL', 340, y);
                ctx.fillText(currentLang === 'uk' ? 'РОЗМІРИ' : 'DIMENSIONS', 470, y);
                ctx.fillText(currentLang === 'uk' ? 'К-СТЬ' : 'QTY', 590, y);
                ctx.fillText(currentLang === 'uk' ? 'ЦІНА' : 'PRICE', 660, y);
                ctx.fillText(currentLang === 'uk' ? 'СУМА' : 'TOTAL', 740, y);

                ctx.strokeStyle = '#e2e8f0';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(36, y + 10);
                ctx.lineTo(width - 36, y + 10);
                ctx.stroke();

                y += 24;

                if (invItems.length === 0) {
                    ctx.fillStyle = '#94a3b8';
                    ctx.font = '14px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
                    ctx.fillText(currentLang === 'uk' ? 'Немає вибраних виробів' : 'No selected products', 36, y + 20);
                    y += rowHeight;
                } else {
                    invItems.forEach((it, idx) => {
                        const q = it.qty || 1;
                        const price = parseFloat(it.price || 0).toFixed(2);
                        const sum = (parseFloat(it.price || 0) * q).toFixed(2);
                        const dims = (it.len && it.width) ? (it.len + ' × ' + it.width + ' мм') : '-';

                        ctx.fillStyle = '#334155';
                        ctx.font = '13px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
                        ctx.fillText(String(idx + 1), 36, y);

                        ctx.fillStyle = '#0f172a';
                        ctx.font = 'bold 13px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
                        const nameText = it.name.length > 28 ? it.name.substring(0, 26) + '...' : it.name;
                        ctx.fillText(nameText, 80, y);

                        ctx.fillStyle = '#64748b';
                        ctx.font = '13px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
                        const matText = (it.material || '-').length > 16 ? (it.material || '-').substring(0, 14) + '...' : (it.material || '-');
                        ctx.fillText(matText, 340, y);
                        ctx.fillText(dims, 470, y);
                        ctx.fillText(String(q) + (currentLang === 'uk' ? ' шт' : ' pcs'), 590, y);
                        ctx.fillText(price, 660, y);

                        ctx.fillStyle = '#0f172a';
                        ctx.font = 'bold 13px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
                        ctx.fillText(sum + ' ' + t('curr'), 740, y);

                        ctx.strokeStyle = '#f1f5f9';
                        ctx.beginPath();
                        ctx.moveTo(36, y + 14);
                        ctx.lineTo(width - 36, y + 14);
                        ctx.stroke();

                        y += rowHeight;
                    });
                }

                // Footer Box - with guaranteed ample margin below
                y += 14;
                ctx.fillStyle = '#f8fafc';
                ctx.fillRect(36, y, width - 72, footerBoxHeight);
                ctx.strokeStyle = '#e2e8f0';
                ctx.lineWidth = 1;
                ctx.strokeRect(36, y, width - 72, footerBoxHeight);

                ctx.fillStyle = '#334155';
                ctx.font = '14px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
                ctx.fillText((currentLang === 'uk' ? 'Разом товарів: ' : 'Total items: ') + totalQty + (currentLang === 'uk' ? ' шт.' : ' pcs.'), 56, y + 38);

                ctx.fillStyle = '#0f172a';
                ctx.font = 'bold 16px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
                ctx.fillText((currentLang === 'uk' ? 'ДО СПЛАТИ: ' : 'TOTAL TO PAY: ') + grandTotal.toFixed(2) + ' ' + t('curr'), 520, y + 38);

                canvas.toBlob((blob) => resolve({ blob, canvas }), 'image/png');
            });
        }

        window.downloadInvoicePng = function() {
            createInvoiceCanvasBlob().then(({ blob }) => {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                const today = new Date().toISOString().slice(0, 10);
                a.href = url;
                a.download = (currentLang === 'uk' ? 'Накладна' : 'Invoice') + '_' + today + '.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                showToast(currentLang === 'uk' ? 'Картинку накладної завантажено' : 'Invoice image downloaded', 'success');
            });
        };

        window.copyInvoicePng = function() {
            createInvoiceCanvasBlob().then(({ blob }) => {
                if (navigator.clipboard && window.ClipboardItem) {
                    navigator.clipboard.write([
                        new ClipboardItem({ 'image/png': blob })
                    ]).then(() => {
                        showToast(currentLang === 'uk' ? 'Накладну скопійовано! Вставте в будь-який чат (Ctrl+V)' : 'Invoice copied! Paste into any chat (Ctrl+V)', 'success');
                    }).catch(() => {
                        window.downloadInvoicePng();
                    });
                } else {
                    window.downloadInvoicePng();
                }
            });
        };

        window.downloadInvoicePdf = function() {
            const { items: invItems, dateStr, totalQty, grandTotal } = getInvoiceDataForExport();
            const printWin = window.open('', '_blank', 'width=840,height=900');
            if (!printWin) {
                showToast(currentLang === 'uk' ? 'Дозвольте спливаючі вікна для друку' : 'Allow popups to print', 'error');
                return;
            }

            const rowsHtml = invItems.map((it, idx) => {
                const q = it.qty || 1;
                const price = parseFloat(it.price || 0).toFixed(2);
                const sum = (parseFloat(it.price || 0) * q).toFixed(2);
                const dims = (it.len && it.width) ? (it.len + ' × ' + it.width + ' мм') : '-';
                return '<tr>' +
                    '<td style="text-align:center;">' + (idx + 1) + '</td>' +
                    '<td><strong>' + escapeHtml(it.name) + '</strong></td>' +
                    '<td>' + escapeHtml(it.material || '-') + '</td>' +
                    '<td>' + dims + '</td>' +
                    '<td style="text-align:center;">' + q + '</td>' +
                    '<td style="text-align:right;">' + price + ' ' + t('curr') + '</td>' +
                    '<td style="text-align:right; font-weight:bold;">' + sum + ' ' + t('curr') + '</td>' +
                '</tr>';
            }).join('');

            printWin.document.write(
                '<!DOCTYPE html><html><head><meta charset="utf-8">' +
                '<title>' + (currentLang === 'uk' ? 'Накладна' : 'Invoice') + ' - ' + dateStr + '</title>' +
                '<style>' +
                    'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 24px; color: #1e293b; }' +
                    '.header { background: #24272a; color: #ffffff; padding: 20px 24px; border-radius: 6px; margin-bottom: 24px; }' +
                    '.header h1 { margin: 0 0 6px 0; font-size: 20px; }' +
                    '.header p { margin: 0; color: #94a3b8; font-size: 13px; }' +
                    'table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }' +
                    'th, td { border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 13px; }' +
                    'th { background: #f8fafc; font-weight: bold; }' +
                    '.total-card { background: #f8fafc; border: 1px solid #cbd5e1; padding: 16px; border-radius: 6px; display: flex; justify-content: space-between; font-size: 15px; }' +
                    '@media print { body { padding: 0; } @page { margin: 1.5cm; } }' +
                '</style></head><body>' +
                '<div class="header"><h1>' + (currentLang === 'uk' ? 'РОЗРАХУНОК ЗАМОВЛЕННЯ' : 'ORDER CALCULATION') + '</h1><p>' + dateStr + '</p></div>' +
                '<table><thead><tr>' +
                    '<th>№</th><th>' + (currentLang === 'uk' ? 'Назва' : 'Name') + '</th><th>' + (currentLang === 'uk' ? 'Матеріал' : 'Material') + '</th><th>' + (currentLang === 'uk' ? 'Розміри' : 'Dimensions') + '</th><th>' + (currentLang === 'uk' ? 'К-сть' : 'Qty') + '</th><th>' + (currentLang === 'uk' ? 'Ціна' : 'Price') + '</th><th>' + (currentLang === 'uk' ? 'Сума' : 'Total') + '</th>' +
                '</tr></thead><tbody>' +
                (rowsHtml || '<tr><td colspan="7" style="text-align:center;">' + (currentLang === 'uk' ? 'Немає виробів' : 'No items') + '</td></tr>') +
                '</tbody></table>' +
                '<div class="total-card">' +
                    '<div>' + (currentLang === 'uk' ? 'Разом одиниць товару' : 'Total items') + ': <strong>' + totalQty + ' шт.</strong></div>' +
                    '<div>' + (currentLang === 'uk' ? 'До сплати' : 'Grand Total') + ': <strong style="font-size:18px;">' + grandTotal.toFixed(2) + ' ' + t('curr') + '</strong></div>' +
                '</div>' +
                '<script>window.onload = function() { window.print(); };</' + 'script>' +
                '</body></html>'
            );
            printWin.document.close();
        };

        window.downloadInvoiceExcel = function() {
            const { items: invItems, dateStr, totalQty, grandTotal } = getInvoiceDataForExport();
            let csv = '\uFEFF'; // UTF-8 BOM
            csv += (currentLang === 'uk' ? 'РОЗРАХУНОК ЗАМОВЛЕННЯ' : 'ORDER CALCULATION') + ';;;\n';
            csv += (currentLang === 'uk' ? 'Дата' : 'Date') + ': ' + dateStr + ';;;\n\n';
            csv += '№;' + (currentLang === 'uk' ? 'Назва виробу' : 'Product name') + ';' + (currentLang === 'uk' ? 'Матеріал' : 'Material') + ';' + (currentLang === 'uk' ? 'Розміри' : 'Dimensions') + ';' + (currentLang === 'uk' ? 'К-сть' : 'Qty') + ';' + (currentLang === 'uk' ? 'Ціна за од.' : 'Unit price') + ';' + (currentLang === 'uk' ? 'Сума' : 'Total') + '\n';

            function cleanCsvField(val) {
                let s = (val === null || val === undefined) ? '' : String(val);
                if (/^[=+\-@]/.test(s)) {
                    s = "'" + s;
                }
                if (s.includes(';') || s.includes('"') || s.includes('\n') || s.includes('\r')) {
                    return '"' + s.replace(/"/g, '""') + '"';
                }
                return s;
            }

            invItems.forEach((it, idx) => {
                const q = it.qty || 1;
                const price = parseFloat(it.price || 0).toFixed(2);
                const sum = (parseFloat(it.price || 0) * q).toFixed(2);
                const dims = (it.len && it.width) ? (it.len + 'x' + it.width + ' mm') : '-';
                const name = cleanCsvField(it.name);
                const mat = cleanCsvField(it.material || '');
                csv += (idx + 1) + ';' + name + ';' + mat + ';' + dims + ';' + q + ';' + price + ';' + sum + '\n';
            });

            csv += '\n;;;;' + (currentLang === 'uk' ? 'РАЗОМ' : 'TOTAL') + ':;' + totalQty + ';' + grandTotal.toFixed(2) + ' ' + t('curr') + '\n';

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            const today = new Date().toISOString().slice(0, 10);
            a.href = url;
            a.download = (currentLang === 'uk' ? 'Накладна' : 'Invoice') + '_' + today + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast(currentLang === 'uk' ? 'Таблицю Excel успішно завантажено' : 'Excel table downloaded', 'success');
        };

        window.shareInvoice = function() {
            createInvoiceCanvasBlob().then(({ blob }) => {
                const today = new Date().toISOString().slice(0, 10);
                const file = new File([blob], 'Invoice_' + today + '.png', { type: 'image/png' });
                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    navigator.share({
                        files: [file],
                        title: currentLang === 'uk' ? 'Розрахунок замовлення' : 'Order Calculation',
                        text: (currentLang === 'uk' ? 'Розрахунок замовлення від ' : 'Order calculation from ') + today
                    }).catch(() => {});
                } else if (navigator.share) {
                    const { dateStr, totalQty, grandTotal } = getInvoiceDataForExport();
                    navigator.share({
                        title: currentLang === 'uk' ? 'Розрахунок замовлення' : 'Order Calculation',
                        text: (currentLang === 'uk' ? 'Розрахунок замовлення' : 'Order Calculation') + ' (' + dateStr + '): ' + totalQty + ' шт. на суму ' + grandTotal.toFixed(2) + ' ' + t('curr')
                    }).catch(() => {});
                } else {
                    window.downloadInvoicePng();
                }
            });
        };

        window.emailInvoice = function() {
            const { items: invItems, dateStr, totalQty, grandTotal } = getInvoiceDataForExport();
            const subject = encodeURIComponent((currentLang === 'uk' ? 'Розрахунок замовлення' : 'Order Calculation') + ' (' + dateStr + ')');
            let body = (currentLang === 'uk' ? 'РОЗРАХУНОК ЗАМОВЛЕННЯ' : 'ORDER CALCULATION') + '\n';
            body += (currentLang === 'uk' ? 'Дата: ' : 'Date: ') + dateStr + '\n\n';

            invItems.forEach((it, idx) => {
                const q = it.qty || 1;
                const price = parseFloat(it.price || 0).toFixed(2);
                const sum = (parseFloat(it.price || 0) * q).toFixed(2);
                const dims = (it.len && it.width) ? (' (' + it.len + '×' + it.width + ' мм)') : '';
                body += (idx + 1) + '. ' + it.name + dims + ' — ' + q + ' шт. × ' + price + ' = ' + sum + ' ' + t('curr') + '\n';
            });

            body += '\n' + (currentLang === 'uk' ? 'Разом товарів: ' : 'Total items: ') + totalQty + ' шт.\n';
            body += (currentLang === 'uk' ? 'ДО СПЛАТИ: ' : 'TOTAL TO PAY: ') + grandTotal.toFixed(2) + ' ' + t('curr') + '\n';

            window.location.href = 'mailto:?subject=' + subject + '&body=' + encodeURIComponent(body);
        };

        window.escapeHtml = function(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

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
        
        // --- МОДАЛЬНЕ ВІКНО ДОДАВАННЯ ВИРОБУ В КАТАЛОГ ---
        window.openAddCatalogItemModal = function() {
            const modal = document.getElementById('modal-add-catalog-item');
            if (!modal) return;
            const nameInput = document.getElementById('cat-add-name');
            const matSelect = document.getElementById('cat-add-mat');
            const lenInput = document.getElementById('cat-add-len');
            const widthInput = document.getElementById('cat-add-width');
            const qtyInput = document.getElementById('cat-add-qty');
            const priceInput = document.getElementById('cat-add-price');
            const invCheckbox = document.getElementById('cat-add-to-inv');

            if (nameInput) nameInput.value = '';
            if (matSelect) {
                matSelect.innerHTML = materials.map(m => '<option value="' + escapeHtml(m.id) + '" data-name="' + escapeHtml(m.name) + '" data-rate="' + m.price + '">' + escapeHtml(m.name) + ' (' + m.price + ' ' + t('curr') + '/см²)</option>').join('');
            }
            if (lenInput) lenInput.value = '1000';
            if (widthInput) widthInput.value = '500';
            if (qtyInput) qtyInput.value = '1';
            if (invCheckbox) invCheckbox.checked = false;

            recalcCatalogModalPrice();
            modal.style.display = 'flex';
            if (nameInput) nameInput.focus();
        };

        window.closeAddCatalogItemModal = function() {
            const modal = document.getElementById('modal-add-catalog-item');
            if (modal) modal.style.display = 'none';
        };

        window.recalcCatalogModalPrice = function() {
            const matSelect = document.getElementById('cat-add-mat');
            const lenInput = document.getElementById('cat-add-len');
            const widthInput = document.getElementById('cat-add-width');
            const priceInput = document.getElementById('cat-add-price');
            if (!matSelect || !lenInput || !widthInput || !priceInput) return;

            const opt = matSelect.selectedOptions ? matSelect.selectedOptions[0] : null;
            const rate = opt ? (parseFloat(opt.dataset.rate) || 0) : 0;
            const len = parseFloat(lenInput.value) || 0;
            const width = parseFloat(widthInput.value) || 0;
            const area = (len * width) / 100;
            const calculated = area * rate;
            priceInput.value = calculated > 0 ? (Math.round(calculated * 10000) / 10000) : '0';
        };

        window.saveCatalogItemFromModal = function() {
            const nameInput = document.getElementById('cat-add-name');
            const matSelect = document.getElementById('cat-add-mat');
            const lenInput = document.getElementById('cat-add-len');
            const widthInput = document.getElementById('cat-add-width');
            const qtyInput = document.getElementById('cat-add-qty');
            const priceInput = document.getElementById('cat-add-price');
            const invCheckbox = document.getElementById('cat-add-to-inv');

            const name = nameInput ? nameInput.value.trim() : '';
            if (!name) {
                showToast(t('err_enter_name'), 'error');
                if (nameInput) nameInput.focus();
                return;
            }

            const len = parseFloat(lenInput ? lenInput.value : 0) || 0;
            const width = parseFloat(widthInput ? widthInput.value : 0) || 0;
            if (len <= 0 || width <= 0) {
                showToast(t('err_invalid_dims'), 'error');
                return;
            }

            const qty = parseInt(qtyInput ? qtyInput.value : 1, 10) || 1;
            const price = parseFloat(priceInput ? priceInput.value : 0);
            if (isNaN(price) || price < 0) {
                showToast(t('err_invalid_price'), 'error');
                return;
            }

            const matOption = matSelect && matSelect.selectedOptions ? matSelect.selectedOptions[0] : null;
            const matId = matSelect ? matSelect.value : '';
            const matName = matOption ? (matOption.getAttribute('data-name') || matOption.textContent.split(' (')[0].trim()) : '';
            const addToInvoice = !!(invCheckbox && invCheckbox.checked);

            const newItem = {
                id: 'p_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
                name: name,
                material: matName,
                material_id: matId,
                len: len,
                width: width,
                area_cm2: (len * width) / 100,
                price: price,
                qty: qty,
                in_invoice: addToInvoice,
                selected: addToInvoice
            };

            items.unshift(newItem);
            saveData(true);
            renderItems();
            renderCatalogTab();
            updateCalculations();
            closeAddCatalogItemModal();
            showToast(t('catalog_item_added'));
        };

});
        loadData();
    })();
