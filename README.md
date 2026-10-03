# WP Calculator — Universal Product & Material Cost Calculator for WordPress

[English](#english) | [Українська версія](#українська-версія)

---

## English

A high-performance, universal calculation, estimation, and invoicing plugin for WordPress. Designed for manufacturers, workshops, craftspeople, fabricators, and businesses calculating costs for any materials (wood, metal, acrylic/plastics, glass, stone, fabrics, composites, sheet goods, and more).

### 🌟 Key Features

1. **Universal Dimensional Cost Calculator:**
   - Calculates part and product costs based on dimensions in millimeters (Length × Width).
   - Real-time area computation in square centimeters ($\text{cm}^2$).
   - High-precision pricing per $1\text{ cm}^2$ with arbitrary decimal precision (e.g. `0.20123`).
   - One-click transfer of calculated dimensions and pricing into the active product form.

2. **Catalog & Order Management:**
   - Save custom products with names, associated materials, dimensions, and unit prices.
   - Fixed historical prices: updating a material rate in the directory does not alter already saved catalog items.
   - Live quantity editor directly in the table.
   - Selection checkboxes to accurately pick items included in the invoice and totals.
   - Dedicated **All Products (Catalog)** tab with a direct modal to add new products directly into catalog without switching tabs.
   - Duplicate with edit modal: clone any item with an immediate adjustment popup.
   - Smooth **Drag-and-Drop (⠿)** row reordering with accent indicator lines and immediate database persistence.
   - Order inclusion status badges (`In Invoice` / `Not in Invoice`).

3. **Custom Materials Directory:**
   - Create and manage unlimited materials (metals, woods, plastics, stone, fabrics, sheet materials, composites).
   - Edit material names and rates per $\text{cm}^2$ dynamically.
   - Responsive layout: desktop displays the materials directory in a convenient right-hand column; mobile automatically stacks it at the very top.

4. **Invoice & Estimation View:**
   - Isolated clean invoice view presenting only selected order items.
   - Order estimate header with automatic current date formatting.
   - Column visibility controls: dynamically toggle Material, Dimensions, Price/pc, Qty, and Total before exporting.

5. **Multi-Format Export & Sharing:**
   - **Download PNG:** high-resolution graphic export of the complete invoice.
   - **Copy Image:** copies the generated invoice image directly to the system clipboard for immediate pasting into chat or messaging apps.
   - **Download PDF:** clean document generation formatted for print and client handoff.
   - **Download Excel:** structured `.xls` table export for accounting or inventory workflows.
   - **Share:** triggers the native Web Share API on mobile devices and modern desktop browsers.
   - **Email:** opens default email client pre-populated with subject and order summary.

6. **Appearance & Custom Branding:**
   - Dedicated **Appearance** tab with live accent color picker.
   - Pre-configured color presets (tomchik green `#95b504`, dark graphite `#24272a`, red `#dc2626`, blue `#2563eb`, purple `#7c3aed`, orange `#ea580c`) plus any hex color.
   - Instant real-time UI updates without page reloads. Custom-styled checkboxes and radio buttons matching the chosen accent color.

7. **Bilingual Support (English & Ukrainian):**
   - Seamless language switcher in the Settings tab.
   - Server-side translation rendering prevents content flashing on initial load.
   - Language preferences are stored persistently in WordPress options and local browser storage.

8. **Zero Loss Data Protection & Server Security:**
   - Multi-tier storage in WordPress `wp_options` with automatic recovery and complete backward-compatible merging across all legacy keys.
   - Robust LocalStorage browser mirror: automatic state recovery if database is empty or uninitialized.
   - Server-side data sanitization and validation (whitelisted types, dimension bounds, color regex).
   - One-click JSON data export (Backup) preserving all settings, materials, and items.
   - CSV formula injection protection during export and unified selection filtering across UI and exports.
   - Safe uninstallation: database records are preserved upon plugin deletion unless explicitly configured otherwise.

9. **Zero-Load Architecture & Optimization:**
   - **100% idle on public frontend requests:** zero CSS/JS loaded for regular site visitors, zero database queries, zero overhead.
   - In-memory static caching prevents duplicate queries during admin rendering.
   - Debounced AJAX synchronization prevents server request flooding during rapid data entry.
   - Transient caching for external requests prevents admin dashboard freezes.

10. **Built-in GitHub Auto-Updater:**
    - Checks GitHub Releases directly from the WordPress Settings tab.
    - One-click automated updates and same-version reinstallation.
    - Release changelog viewer and package validation.

---

### 🚀 Installation

1. Download `wp-calculator.zip` from the latest GitHub Release.
2. Go to **Plugins -> Add New -> Upload Plugin** in your WordPress dashboard, select the ZIP archive, and click **Install Now**.
3. Activate the plugin.
4. Access the plugin via the **«Калькулятор виробів» (Product Calculator)** menu in the WordPress admin panel.

---

## Українська версія

Універсальний, високопродуктивний плагін для WordPress для розрахунку вартості виробів, створення кошторисів та накладних. Розроблений для майстерень, виробництв, розкрійних цехів, крафтярів та бізнесу для розрахунку виробів із будь-яких матеріалів (дерево, метал, пластик, акрил, скло, камінь, тканини, композити, листові матеріали тощо).

### 🌟 Основні можливості

1. **Універсальний швидкий калькулятор вартості:**
   - Розрахунок вартості деталей та виробів за розмірами у міліметрах (довжина × ширина).
   - Миттєвий автоматичний розрахунок площі в $\text{см}^2$.
   - Точна ціна за $1\text{ см}^2$ із підтримкою довільної кількості знаків після коми (наприклад, `0.20123 грн`).
   - Кнопка «Внести у виріб ↓» для миттєвої передачі розрахованих розмірів та вартості у форму збереження товару.

2. **Каталог збережених виробів та замовлень:**
   - Збереження назви виробу, обраного матеріалу, розмірів у мм та фіксованої ціни за 1 шт.
   - Фіксація цін: зміна тарифу матеріалу в довіднику не змінює вартість уже створених виробів у каталозі.
   - Зміна кількості прямо в таблиці без збоїв виділення тексту мишкою.
   - Чекбокси точного вибору позицій, що входять у накладну та враховуються у підсумках.
   - Окрема вкладка **«Всі вироби» (Каталог)** зі створенням виробу напряму у власному модальному вікні без перемикання на калькулятор.
   - Копіювання з модальним вікном: швидке створення схожих позицій із можливістю відредагувати назву, матеріал та розміри перед збереженням.
   - Плавний **Drag-and-Drop (⠿)**: зміна порядку виробів із лінією-індикатором місця вставки та миттєвим збереженням у базі.
   - Бейджі статусу присутності позицій у накладній (`В накладній` / `Не в накладній`).

3. **Довідник універсальних матеріалів:**
   - Додавання необмеженої кількості матеріалів (дерево, метали, пластики, скло, камінь, тканини, композити).
   - Редагування назви та тарифу матеріалу за $\text{см}^2$ у будь-який час.
   - Адаптивна розкладка: на ПК довідник зручно розташований у правому стовпчику, на смартфонах — автоматично піднімається нагору.

4. **Режим накладної (кошторису):**
   - Відокремлений режим перегляду накладної виключно для обраних позицій замовлення.
   - Шапка «РОЗРАХУНОК ЗАМОВЛЕННЯ» з автоформатуванням поточної дати.
   - Гнучкий вибір колонок: чекбокси вмикання/вимикання колонок (Матеріал, Розміри, Ціна/шт, К-сть, Сума).

5. **Експорт у різні формати та поширення:**
   - **Завантажити PNG:** чітке графічне зображення всієї накладної з підсумком.
   - **Скопіювати картинку:** миттєве копіювання зображення накладної в буфер обміну для відправки у месенджери (Viber, Telegram, WhatsApp).
   - **Завантажити PDF:** чистий друкований документ для клієнта.
   - **Завантажити Excel:** таблиця `.xls` для ведення бухгалтерії або обліку.
   - **Поділитися:** виклик системного діалогу поширення на смартфонах та ПК.
   - **Надіслати на Email:** запуск поштового клієнта зі сформованою темою та текстом замовлення.

6. **Оформлення та фірмовий стиль:**
   - Вкладка **«Оформлення»** із палітрою акцентного кольору та Color Picker.
   - Готові пресети кольорів (фірмовий зелений tomchik `#95b504`, темний графіт `#24272a`, червоний `#dc2626`, синій `#2563eb`, індиго `#7c3aed`, бурштиновий `#ea580c`).
   - Миттєве застосування без перезавантаження. Стилізовані чекбокси та радіокнопки в акцентному кольорі.

7. **Двомовність (Українська та English):**
   - Перемикання мови інтерфейсу в налаштуваннях.
   - Початковий рендеринг мови на стороні сервера виключає миготіння тексту при завантаженні.
   - Надійне збереження вибору в базі даних WordPress та LocalStorage браузера.

8. **Абсолютний захист даних від втрати та серверна безпека:**
   - Багаторівневе збереження у `wp_options` з повним об'єднанням усіх історичних ключів без втрати даних.
   - Надійне дзеркало у LocalStorage браузера з авто-відновленням при порожній або скинутій серверній базі.
   - Серверна санітизація та валідація всіх вхідних даних (розміри, ціни, кольори, мова, типи).
   - Експорт повної резервної копії в JSON (Бекап) включно з усіма персональними налаштуваннями.
   - Захист від CSV formula injection при експорті та єдина сувора логіка вибору позицій в інтерфейсі й файлах.
   - Безпечне видалення: дані плагіна за замовчуванням зберігаються в системі навіть після деінсталяції плагіна, якщо не активовано примусове очищення.

9. **Нульове навантаження на сайт та оптимізація:**
   - **100% нульовий вплив на публічну частину сайту:** жодних файлів JS/CSS для звичайних відвідувачів сайту, жодних запитів до бази даних.
   - Статичне in-memory кешування запобігає дублюванню SQL-запитів під час відкриття сторінки.
   - Дебаунс збереження (250 мс) захищає сервер від спаму запитами при частих кліках чи швидкому вводі.
   - Кешування помилок зовнішніх запитів GitHub запобігає зависанню адмінки при тимчасових мережевих збоях.

10. **Вбудований оновлювач з GitHub:**
    - Перевірка нових версій безпосередньо з вкладки «Налаштування».
    - Оновлення або перевстановлення поточної версії в один клік через AJAX.
    - Відображення списку змін (changelog) релізу.

---

### 🚀 Встановлення

1. Завантажте `wp-calculator.zip` з розділу останнього релізу на GitHub.
2. В адмін-панелі сайту відкрийте **Плагіни -> Додати новий -> Завантажити плагін**, оберіть архів та натисніть **Встановити зараз**.
3. Активуйте плагін.
4. Перейдіть до розділу **«Калькулятор виробів»** у бічному меню панелі WordPress.
