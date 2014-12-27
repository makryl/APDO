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
    ->fetchAll();
```

But... Why not go on?

```php
$fruits = $apdo
    ->from('fruit')
    ->limit(10)
    ->fetchAll();
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
    ->fetchOne();
```

Do you need only fruit color?

```php
$color = $apdo
    ->from('fruit')
    ->key(123)
    ->fetchCell('color');
```

Do you need only fruit color and name?

```php
list($color, $name) = $apdo
    ->from('fruit')
    ->key(123)
    ->fetchRow(['color', 'name']);
```

Do you need ID-indexed array of red fruit names?

```php
$red_fruits_id_name = $apdo
    ->from('fruit')
    ->key('red', 'color')
    ->fetchPairs('name'/*, 'id'*/);
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

The MIT License (MIT)

Copyright © 2013-2015 Maksim Krylosov <aequiternus@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
