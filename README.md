
Fork from https://github.com/damirka/yii2-jwt

# yii2-jwt

JWT implementation for Yii2 Authorization process

For details see [JWT official website](https://jwt.io/introduction/).

## Installation

To install (only master is available now) run:
```
    composer require "msheng/yii2-jwt:~1.0.0"
```
Or add this line to *require* section of composer.json:
```
    "msheng/yii2-jwt": "~1.0.0"
```

## Usage

There is only one trait - *UserTrait* - which gives you 5 methods for
authorization and JWT-management in User model

### project

Your project need to be an [yii2-app-advanced](https://github.com/yiisoft/yii2-app-advanced) , and here is the [guide](https://github.com/yiisoft/yii2-app-advanced/blob/master/docs/guide/start-installation.md)

Set up:

### In common/config/params.php

```PHP
<?php
$params = [
    'JWT_SECRET' => 'your_secret',
    'JWT_EXPIRE' => 10*24*60*60
]

```

### In controller:

```PHP
<?php

// ...
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;

class BearerAuthController extends \yii\rest\ActiveController
{
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'authenticator' => [
                'class' => CompositeAuth::className(),
                'authMethods' => [HttpBearerAuth::className(),],
            ]
        ]);
    }
}
```

### In User model:

```PHP
<?php

// ...

use yii\db\ActiveRecord;
use yii\web\IdentityInterface

class User extends ActiveRecord implements IdentityInterface
{
    // Use the trait in your User model
    use \msheng\JWT\UserTrait;
}
```

### Get the jwt

```PHP
<?php
// $user is an User object
$token = $user->getJwt()
```


