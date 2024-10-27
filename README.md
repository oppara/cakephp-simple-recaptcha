[![Build Status](https://img.shields.io/github/actions/workflow/status/oppara/cakephp-simple-recaptcha/ci.yml)](https://github.com/oppara/cakephp-simple-recaptcha/actions?query=workflow%3ACI+branch%3Amain)

# CakePHP plugin to handle Google Recaptcha

If both v2 and v3 are configured, it will act as v3 and use v2 as a fallback.
If only v3 is configured, it will act as v3.
And V2 so.

## Requirements

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

### Use V3 with V2 fallback

<details>
<summary>Click to expand</summary>

`config/app.php`
```
   'Recaptcha' => [
        'v3' => [
            'site_key' => 'your_site_key',
            'secret_key' => 'your_secret',
        ],
        'v2' => [
            'site_key' => 'your_site_key',
            'secret_key' => 'your_secret',
        ],
    ],
```

`src/Controller/InquiryController.php`
```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Client\Exception\NetworkException;
use Cake\Http\Client\Exception\RequestException;
use Oppara\SimpleRecaptcha\Exception\RecaptchaV3Exception;

class InquiryController extends AppController

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
                    return $this->redirect(['action' => 'complete']);
                }

                $this->log(json_encode($this->Recaptcha->getResult()), LOG_ERR);
                $this->Flash->error('recaptcha error.');

            } catch (RecaptchaV3Exception $e) {

                $this->log($e->getMessage(), LOG_ERR);
                $this->Flash->error('You have been identified as a robot. Please try again.');

                return $this->redirect(['action' => 'input']);

            } catch (NetworkException | RequestException $e) {
                $this->log($e->getMessage(), LOG_ERR);
                $this->Flash->error('network error.');
            }
        }
    }

    public function complete()
    {
    }
}
```

`templates/layout/defalult.php`
```

<?= $this->fetch('scriptBottom'); ?>
</body>
</html>
```

`templates/Inquiry/input.php`
```
<?= $this->Form->create() ?>
<?= $this->Form->control('email') ?>
<?= $this->Form->button('submit') ?>
<?= $this->Recaptcha->hidden(); ?>
<?= $this->Recaptcha->checkbox(); ?>
<?= $this->Form->end(); ?>
```
</details>

### Use v3

<details>
<summary>Click to expand</summary>

`config/app.php`
```
   'Recaptcha' => [
        'v3' => [
            'site_key' => 'your_site_key',
            'secret_key' => 'your_secret',
        ],
    ],
```

`src/Controller/InquiryController.php`
```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Client\Exception\NetworkException;
use Cake\Http\Client\Exception\RequestException;

class InquiryController extends AppController

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Oppara/SimpleRecaptcha.Recaptcha');
    }

    public function index()
    {
        if ($this->request->is('post')) {

            try {
                if ($this->Recaptcha->verify()) {
                    return $this->redirect(['action' => 'complete']);
                }

                $this->log(json_encode($this->Recaptcha->getResult()), LOG_ERR);
                $this->Flash->error('recaptcha error.');

            } catch (NetworkException | RequestException $e) {
                $this->log($e->getMessage(), LOG_ERR);
                $this->Flash->error('network error.');
            }
        }
    }

    public function complete()
    {
    }
}
```

`templates/layout/defalult.php`
```

<?= $this->fetch('scriptBottom'); ?>
</body>
</html>
```

`templates/Inquiry/input.php`
```
<?= $this->Form->create() ?>
<?= $this->Form->control('email') ?>
<?= $this->Form->button('submit') ?>
<?= $this->Recaptcha->hidden(); ?>
<?= $this->Form->end(); ?>
```
</details>

### Use v2

<details>
<summary>Click to expand</summary>

`config/app.php`
```
   'Recaptcha' => [
        'v2' => [
            'site_key' => 'your_site_key',
            'secret_key' => 'your_secret',
        ],
    ],
```

`src/Controller/InquiryController.php`
```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Client\Exception\NetworkException;
use Cake\Http\Client\Exception\RequestException;
use Oppara\SimpleRecaptcha\Exception\RecaptchaV3Exception;

class InquiryController extends AppController

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
                    return $this->redirect(['action' => 'complete']);
                }

                $this->log(json_encode($this->Recaptcha->getResult()), LOG_ERR);
                $this->Flash->error('recaptcha error.');

            } catch (NetworkException | RequestException $e) {
                $this->log($e->getMessage(), LOG_ERR);
                $this->Flash->error('network error.');
            }
        }
    }

    public function complete()
    {
    }
}
```

`templates/layout/defalult.php`
```

<?= $this->fetch('scriptBottom'); ?>
</body>
</html>
```

`templates/Inquiry/input.php`
```
<?= $this->Form->create() ?>
<?= $this->Form->control('email') ?>
<?= $this->Form->button('submit') ?>
<?= $this->Recaptcha->checkbox('data-callback="verifyCallback" data-expired-callback="expiredCallback"'); ?>
<?= $this->Form->end(); ?>
```
</details>


## License

Licensed under the [MIT](http://www.opensource.org/licenses/mit-license.php) License.
