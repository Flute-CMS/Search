# Search

`Search` добавляет на сайт быстрый, расширяемый глобальный поиск с красивым модальным интерфейсом и интеграцией в навбар. Модули могут подключать свои источники (пользователи, страницы, навигация, новости, вики и т.п.), а администратор - управлять темами поиска и лимитами, сохраняя безопасность и корректность прав доступа.

**Возможности:**

- **Расширяемая архитектура**: модули добавляют свои источники поиска через провайдеры.
- **Настройки в админке**: включить/выключить поиск, задать min length и лимит результатов, управлять источниками.
- **Безопасный API**: фильтрация входных данных, ограничение параметров, защита от падения отдельных провайдеров.
- **Проверка прав**: страницы с ограничениями не попадают в результаты для пользователя без доступа.
- **Быстрый UX**: debounce + отмена предыдущих запросов, клавиатурная навигация, фильтры по источникам.
- **Мобильная адаптация**: удобное закрытие, прокрутка фильтров, аккуратный лоадер и состояния ошибок.

---

`Search` adds a fast, extensible global search with a premium modal UI and navbar integration. Modules can plug in their own search sources (users, pages, navigation, news, wiki, etc.), while admins can control topics and limits — with security and permission-aware results.

**Features:**

- **Extensible architecture**: modules register search providers as new sources.
- **Admin settings**: enable/disable search, configure min length and result limits, toggle providers.
- **Secure API**: input sanitization, strict parameter bounds, provider failure isolation.
- **Permission-aware results**: restricted pages are hidden for users without access.
- **Fast UX**: debounce + request cancellation, keyboard navigation, provider filters.
- **Mobile-friendly UI**: clear close actions, scrollable filters, polished loader and error states.

## Installation

Download the latest release and install it via the Flute CMS admin panel.

Current version: **1.0.1**

## Authors

- Flames

## Links

- [Flute CMS](https://flute-cms.com)
- [Module page](https://flute-cms.com/market/Search)
