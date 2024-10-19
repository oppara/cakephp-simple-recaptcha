[![Build Status](https://img.shields.io/github/actions/workflow/status/oppara/cakephp-simple-recaptcha/ci.yml)](https://github.com/oppara/cakephp-simple-recaptcha/actions?query=workflow%3ACI+branch%3Amain)

# CakePHP plugin to handle Google Recaptcha V3

# Requirements

* PHP 8.1+
* CakePHP 5.0+
 
## Installation

```
composer require oppara/cakephp-simple-recaptcha
```

## Load plugin

```
bin/cake plugin load Oppara/SimpleRecaptcha
```


## Usage

`config/app.php`
```
   'Recaptcha' => [
        'v3' => [
            'site_key' => 'your_site_key',
            'secret_key' => 'your_secret',
        ],
    ],
```

`src/Controller/SomeAwsomeController.php`
```
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Oppara/SimpleRecaptcha.Recaptcha', [
            'actions' => [
                'input',
            ],
        ]);
    }

    public function input()
    {
        if ($this->request->is('post')) {

            try {
                if ($this->Recaptcha->verify()) {
                    return $this->redirect(['action' => 'confirm']);
                }

                $this->log(json_encode($this->Recaptcha->getResult()), LOG_ERR);
                $this->Flash->error('recaptcha error.');

            } catch (\Cake\Http\Client\Exception\NetworkException | \Cake\Http\Client\Exception\RequestException $e) {
                $this->log($e->getMessage(), LOG_ERR);
                $this->Flash->error('network error.');
            }
        }
    }
```

`templates/layout/defalult.php`
```

<?= $this->fetch('scriptBottom'); ?>
</body>
</html>
```

`templates/SomeAwsome/input.php`
```
<?= $this->Form->create() ?>
<?= $this->Form->control('email') ?>
<?= $this->Recaptcha->hidden(); ?>
<?= $this->Form->button('submit') ?>
<?= $this->Form->end(); ?>
```

## License

Licensed under the [MIT](http://www.opensource.org/licenses/mit-license.php) License.
