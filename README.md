# MathParserPHP
This is a simple port of my MathParser Project in C#. Some functions like integral do not work at this moment.

## How to use
Please include the file math.php and make sure that ArrayList.php is in the same folder
```
$parser = new Parser(new ArrayList());
$ergebnis = $parser ->calculate("2*4^2");

echo $ergebnis;
```
## Credits

The ArrayList class in PHP was created by Tim Anlauf <schranzistorradio@gmx.de>

http://www.phpclasses.org/package/1169-PHP-Class-to-handle-lists.html#information
