# APDO

APDO class represents connection to database.



## Features

- Uses [PDO](http://php.net/manual/book.pdo.php) for database access.
- Lazy connection (established before first query sent to database).
- Stores query log.
- Caches statement results and rows if possible.
- Simple interface to make queries and retrieve results.
- Simple using foreign keys to retrieve referenced data.



## FAQ

*Why PDO?*
PDO already supports many databases. APDO just make it easier to use.

*Why not ActiveRecord?*
Arrays, arrays and again arrays, and function name conventions... boring! No autocomplete. Typo mistakes. I don't want to remember all that keys and conventions.



## Usage

APDO constructor is same as PDO.

Basic usage of PDO looks like this:

```php
$sth = $pdo->prepare('SELECT * FROM fruit LIMIT 10');
$sth->execute();
$fruits = $sth->fetchAll();
```

With APDO it is a bit simpler:

```php
$fruits = $apdo
    ->statement('SELECT * FROM fruit LIMIT 10')
    ->all();
```

But... Why not go on?

```php
$fruits = $apdo
    ->from('fruit')
    ->limit(10)
    ->all();
```

Thats better.

How much rows it was last time, but without limit?

```php
$last_count = $apdo
    ->last()
    ->count();
```

Do you know which fruit you need?

```php
$i_want_this_one = $apdo
    ->from('fruit')
    ->key(123)
    ->one();
```

Do you need only fruit color?

```php
list($color) = $apdo
    ->from('fruit')
    ->key(123)
    ->oneL();
```

Do you need ID-indexed array of red fruit names?

```php
$red_fruits_id_name = $apdo
    ->from('fruit')
    ->fields('id, name') # or ->fields(['id', 'name'])
    ->key('red', 'color')
    ->allK();
```

I don't like cherry, remove it.

```php
$apdo
    ->from('fruit')
    ->key('cherry', 'name')
    ->delete();
```

And all my apples should be green.

```php
$apdo
    ->in('fruit')
    ->key('apple', 'name')
    ->update(['color' => 'green']);
```

I have two trees. What fruits grows there?

```php
$trees = [
    ['id' => 1, 'name' => 'apple tree'],
    ['id' => 2, 'name' => 'orange tree'],
];

$fruits = $apdo
    ->from('fruit')
    ->references($tree, 'fruits', 'tree', 'tree_id')
    ->all();

#   $fruits == [
#       ['id' => 1, 'name' => 'apple1', 'tree_id' => 1,
#               'tree' => &['id' => 1, 'name' => 'apple tree', 'fruits' => &recursion],
#           ],
#       ['id' => 2, 'name' => 'apple2', 'tree_id' => 1,
#               'tree' => &['id' => 1, 'name' => 'apple tree', 'fruits' => &recursion],
#           ],
#       ['id' => 3, 'name' => 'orange', 'tree_id' => 2,
#               'tree' => &['id' => 2, 'name' => 'orange tree', 'fruits' => &recursion],
#           ],
#   ];
```

On which trees my fruits grows?

```php
$fruits = [
    ['id' => 1, 'name' => 'apple1', 'tree' => 1],
    ['id' => 2, 'name' => 'apple2', 'tree' => 1],
    ['id' => 3, 'name' => 'orange', 'tree' => 2],
];

$trees = $apdo
    ->from('tree')
    ->referrers($fruits, 'fruits', 'tree')
    ->all();

#   $trees == [
#       ['id' => 1, 'name' => 'apple tree', 'fruits' => [
#               &['id' => 1, 'name' => 'apple1', 'tree_id' => 1, 'tree' => &reqursion],
#               &['id' => 2, 'name' => 'apple2', 'tree_id' => 1, 'tree' => &reqursion],
#           ]]
#       ['id' => 2, 'name' => 'orange tree', 'fruits' => [
#               &['id' => 3, 'name' => 'orange', 'tree_id' => 2, 'tree' => &reqursion],
#           ]],
#   ];
```

That wasn't full methods list. Just press your favorite ctrl+space and read phpdocs - that's easy.

I WANT your comments, feature requests, bug reports, suggestions, thoughts...



## License

Copyright © 2013 Krylosov Maksim <Aequiternus@gmail.com>

This Source Code Form is subject to the terms of the Mozilla Public
License, v. 2.0. If a copy of the MPL was not distributed with this
file, You can obtain one at http://mozilla.org/MPL/2.0/.
