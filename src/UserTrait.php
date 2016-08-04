<?php

namespace msheng\JWT;

use Firebase\JWT\JWT;

use Yii;
use yii\web\UnauthorizedHttpException;
use yii\web\Request as WebRequest;

/**
 * Trait to handle JWT-authorization process. Should be attached to User model.
 * If there are many applications using user model in different ways - best way
 * is to use this trait only in the JWT related part.
 */
trait UserTrait
{
    /**
     * Getter for exp that's used for generation of JWT
     * @return string secret key used to generate JWT
     */
    protected static function getJwtExpire(){
        return Yii::$app->params['JWT_EXPIRE'];
    }

    /**
     * Getter for secret key that's used for generation of JWT
     * @return string secret key used to generate JWT
     */
    protected static function getSecretKey()
    {
        return Yii::$app->params['JWT_SECRET'];
    }

    /**
     * Getter for "header" array that's used for generation of JWT
     * @return array JWT Header Token param, see http://jwt.io/ for details
     */
    protected static function getHeaderToken()
    {
        return [];
    }

    /**
     * Logins user by given JWT encoded string. If string is correctly decoded
     * - array (token) must contain 'jti' param - the id of existing user
     * @param  string $accessToken access token to decode
     * @return mixed|null          User model or null if there's no user
     * @throws \yii\web\ForbiddenHttpException if anything went wrong
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $secret = static::getSecretKey();
        $errorText = 'Incorrect token';

        // Decode token and transform it into array.
        // Firebase\JWT\JWT throws exception if token can not be decoded
        try {
            $decoded = JWT::decode($token, $secret, [static::getAlgo()]);
        } catch (\Exception $e) {
            throw new UnauthorizedHttpException($errorText);
        }

        $decodedArray = (array) $decoded;

        // If there's no jti param - exception
        if (!isset($decodedArray['jti'])) {
            throw new UnauthorizedHttpException($errorText);
        }

        // JTI is unique identifier of user.
        // For more details: https://tools.ietf.org/html/rfc7519#section-4.1.7
        $id = $decodedArray['jti'];

        return static::findByJTI($id);
    }

    /**
     * Finds User model using static method findOne
     * Override this method in model if you need to complicate id-management
     * @param  integer $id if of user to search
     * @return mixed       User model
     * @throws \yii\web\UnauthorizedHttpException if model is not found
     */
    public static function findByUid($id)
    {
        $model = static::findOne($id);
        $errorText = "Incorrect token";
        // Throw error if user is missing
        if (empty($model)) {
            throw new UnauthorizedHttpException($errorText);
        }
        return $model;
    }

    /**
     * Getter for encryption algorytm used in JWT generation and decoding
     * Override this method to set up other algorytm.
     * @return string needed algorytm
     */
    public static function getAlgo()
    {
        return 'HS256';
    }

    /**
     * Returns some 'id' to encode to token. By default is current model id.
     * If you override this method, be sure that getPayloadUid is updated too
     * @return identifier of user
     */
    public function getPayloadUid()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @param array $payload
     * @return string encoded JWT
     */
    public function getJWT($payload = [])
    {
        $secret = static::getSecretKey();
        $currentTime = time();
        $request = Yii::$app->request;
        $hostInfo = '';

        // There is also a \yii\console\Request that doesn't have this property
        if ($request instanceof WebRequest) {
            $hostInfo = $request->hostInfo;
        }
        $payload['iss'] = $hostInfo;
        $payload['aud'] = $hostInfo;
        $payload['iat'] = $currentTime;
        $payload['nbf'] = $currentTime;

        // Set up user id
        $payload['uid'] = $this->getPayloadUid();
        if (!isset($payload['exp'])) {
            $payload['exp'] = $currentTime + static::getJwtExpire();
        }
        return JWT::encode($payload, $secret, static::getAlgo());
    }
}
