[![Build Status](https://img.shields.io/github/actions/workflow/status/oppara/cakephp-simple-recaptcha/ci.yml)](https://github.com/oppara/cakephp-simple-recaptcha/actions?query=workflow%3ACI+branch%3Amain)

# Google Recaptcha v3 plugin for CakePHP

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
                'index',
                'confirm',
            ],
        ]);
    }

    public function index()
    {
        if ($this->request->is('post')) {

            try {
                if ($this->Recaptcha->verify()) {
                    return $this->redirect(['action' => 'confirm']);
                }

                $this->log(json_encode($this->Recaptcha->getResult()), LOG_ERR);
                $this->Flash->error('recaptcha error.');

            } catch (NetworkException | RequestException $e) {
                $this->log($e->getMessage(), LOG_ERR);
                $this->Flash->error('network error.');
            }
        }
    }
```

`templates/SomeAwsome/index.php`
```
<?= $this->Form->create() ?>
<?= $this->Form->control('email') ?>
<?= $this->Recaptcha->hidden(); ?>
<?= $this->Form->button() ?>
<?= $this->Form->end(); ?>
```

## License

Licensed under the [MIT](http://www.opensource.org/licenses/mit-license.php) License.
