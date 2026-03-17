## Структура файлов

```
src/
└── HtmlDomParser/
	├── Core/                                   # Ядро библиотеки
    │   ├── Node/                               # Классы объектов дерева
    │   │   ├── Node.php                        # Базовый узел
    │   │   ├── Document.php                    # Корневой узел документа
    │   │   ├── Element.php                     # Базовый элемент
    │   │   ├── ErrorElement.php                # Узел-ошибка
    │   │   └── Fragment.php                    # Сущность форматирования текста (RichTextFragmentInterface)
    │   ├── Collection/                         # Коллекции
    │   │   ├── ParserList.php                  # Базовая коллекция
    │   │   ├── ElementList.php                 # Коллекция элементов
    │   │   └── FragmentList.php                # Коллекция сущностей форматирования (RichTextFragmentListInterface)
    │   ├── Context/                            # Контекстная обработка
    │   │   ├── ContextTypeConstant.php         # Хранилище коснтант контекста (вместо ContextTypeConstantsTrait)
    │   |   ├── ContextDataResolver.php         # Определяет data и label для по имени тега (ранее DataResolverInterface)
    │   │   ├── ContextConverter.php            # Создание контекста по правилам
    │   │   ├── NodeContext.php                 # Контекст узла при обходе
    │   │   └── TagContextMap.php               # Правила обработки тегов
    │   ├── Event/                              # Событийная система
    │   │   ├── EventConstant.php               # Хранилище коснтант событий
    │   │   └── EventDispatcher.php             # Диспетчер событий
    │   ├── Error/                              # Обработка ошибок
    │   │   ├── ErrorConstant.php               # Хранилище коснтант ошибок (вместо ErrorConstantsTrait)
    │   │   └── ErrorHandler.php                # Централизованный сбор ошибок
    │   ├── Utilite/                           	# Утилиты
    │   |   └── InlineCollapser.php             # Сервис схлопывания inline-узл
    │   ├── ModuleManager.php                   # Управление модулями
    │   └── Parser.php                          # Главный парсеров
	├── Contract/                               # Интерфейсы (все контракты)
    │   ├── ParserInterface.php
    │   ├── NodeInterface.php
    │   ├── DocumentInterface.php
    │   ├── ElementInterface.php
    │   ├── ErrorElementInterface.php
    │   ├── ParserListInterface.php
    │   ├── ElementListInterface.php
    │   ├── RichTextFragmentListInterface.php
    │   ├── NodeContextInterface.php
    │   ├── ContextConverterInterface.php
    │   ├── TagContextMapInterface.php
    │   ├── ErrorHandlerInterface.php
    │   ├── EventDispatcherInterface.php
    │   ├── ModuleInterface.php
    │   ├── ModuleManagerInterface.php
    │   ├── ContextDataResolverInterface.php
    │   ├── InlineCollapserInterface.php
    │   ├── RichTextFragmentInterface.php
    │   ├── HtmlDomParserException.php
    │   └── InvalidEventListenerException.php
    ├── Exception/                              # Исключения
    │   ├── HtmlDomParserException.php
    │   └── InvalidEventListenerException.php
    └── Module/                                 # Встроенные модули (опционально)
```