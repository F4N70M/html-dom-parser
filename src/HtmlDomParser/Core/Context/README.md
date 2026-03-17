# Контекст

## Состав

**Основные классы:**
- `NodeContext` – Объект контекста узла
- `ContextConverner` – Создает объект контекста узла для обработки, после обработки превращает его в Element
- `ContextDataResolver` – Извлекает основные данные узла следуя конфигурации узла
- `ContextDataResolverRules` – Выдает сонфигурации контекста для узла
**Вспомогательные классы:**
- `ContextTagAlias` – хранит константы алиасов тегов (устаревших)
- `ContextType` – хранит константы типов контекста узла
- `ContextPermission` – хранит константы разрешения обработчиков извлечений данных
- `ContextSrc` – хранит константы источников для извлечения полезных данных узла
- `ContextStyle` – хранит константы оформления тега узла


## Алгоритм

- создается объект `ContextConverter`
	- в нем создается `ContextDataResolverRules`
	- в нем создается `ContextDataResolver`
		- `ContextDataResolver` получает `ContextDataResolverRules` в конструкторе
- вызов метода `nodeToContext` объекта `ContextConverter`
	- получает в аргументах `DOMNode` и родительский `childrenContexrType`
	- получает конфигурацию узла с помощью `ContextDataResolver`
	- проверяет обрабатывать ли узел
	- создает объект контекста узла `NodeContext`
	- возвращает `NodeContext`
- вызов метода `contextToElement`
	- получает в аргументах `NodeContext`
	- извлекает из `NodeContext` полезные данные
	- создает на их основе объект узла `Element`
	- возвращает `Element`

## ContextDataResolverRules

- содержит метод `get` с параметро `nodeName`
- метод `get` возвращает массив содержащий информацию:
	- thisContextType		- типу узла
	- childrenContextType	- тип узлов потомков
	- style					- оформление узла
	- label					- источник извлечения подписи узла
	- value					- источник извлечения целевых данных узла
	- allowedTags			- правила разрешенных тегов потомков
	- daniedTags			- правила запрещенных тегов потомков

## ContextDataResolver




