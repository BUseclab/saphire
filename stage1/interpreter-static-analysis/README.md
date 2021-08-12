Preliminary:
Build a PHP binary with the needed libraries/extensions *linked in*.

Requirements:
* eu-unstrip
* apt-file
* objdump
* readelf

## Get the list of PHP functions and their addresses within the PHP interpreter
1. Build the extension in enum/

1. Run the extension with
```bash
/path/to/php -d "extension=enum/modules/enum.so" enum/do-enum.php > func_to_addr
```

## Run the analysis:
```bash
python3.5 analyze_interpreter.py func_to_addr /path/to/php
```

There are some tools to compare/merge mappings in utils/
