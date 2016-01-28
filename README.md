# servicemanager-migration
Tools for v2-v3 zend-servicemanager migration

Pretty basic right now. It just pumps out your `protected $factories = [ ... ]` with the normalized FQCNs added to the
bottom, if needed. Still, it saves some painful typing :)

## Usage

```bash
composer require --dev kynx/servicemanager-migration
```

Right click on offending class name and select "Copy reference" (err, you do use IntelliJ/PHPStorm?):

```bash
./vendor/bin/smv2v3.php [paste class name]
```

