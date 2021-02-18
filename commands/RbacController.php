<?php

namespace app\commands;


use Yii;
use yii\console\Controller;
use \app\rbac\UserGroupRule;
use \app\rbac\UserProfileOwnerRule;

class RbacController extends Controller
{
    /**
     * @throws \Exception
     */
    public function actionInit()
    {
        $authManager = Yii::$app->authManager;
        $authManager->removeAll();

        //Создаем роли

        $guest = $authManager->createRole('guest');
        $guest->description = 'Гость';
        $buyer = $authManager->createRole('buyer');
        $buyer->description = 'Покупатель';
        $supplier = $authManager->createRole('supplier');
        $supplier->description = 'Поставщик';
        $admin = $authManager->createRole('admin');
        $admin->description = 'Администратор';

        //Создаем разрешения, основанные на имени экшена
        $login = $authManager->createPermission('login');
        $logout = $authManager->createPermission('logout');
        $error = $authManager->createPermission('error');
        $sign_up = $authManager->createPermission('sign-up');
        $index = $authManager->createPermission('index');
        $view = $authManager->createPermission('view');
        $create = $authManager->createPermission('create');
        $update = $authManager->createPermission('update');
        $delete = $authManager->createPermission('delete');
        $profile = $authManager->createPermission('profile');
        $test = $authManager->createPermission('test');


        //Добавляем разрешения в AuthManager

        $authManager->add($login);
        $authManager->add($logout);
        $authManager->add($error);
        $authManager->add($sign_up);
        $authManager->add($index);
        $authManager->add($view);
        $authManager->add($create);
        $authManager->add($update);
        $authManager->add($delete);
        $authManager->add($profile);
        $authManager->add($test);

        //Добавляем правила, основанные на UserExt->group === $user->group
        $userGroupRule = new UserGroupRule();
        $authManager->add($userGroupRule);

        //Добавляем правила UserGroupRule в роли
        $guest->ruleName = $userGroupRule->name;
        $buyer->ruleName = $userGroupRule->name;
        $supplier->ruleName = $userGroupRule->name;
        $admin->ruleName = $userGroupRule->name;

        //Добавляем роли в Yii::$app->authManager
        $authManager->add($guest);
        $authManager->add($buyer);
        $authManager->add($supplier);
        $authManager->add($admin);

        //Добавляем разрешения для роли в Yii::$app->authManager

        //Guest
        $authManager->addChild($guest, $login);
        $authManager->addChild($guest, $error);
        $authManager->addChild($guest, $sign_up);
        $authManager->addChild($guest, $index);
        $authManager->addChild($guest, $view);

        //Покупатель
        $authManager->addChild($buyer, $update);
        $authManager->addChild($buyer, $create);
        $authManager->addChild($buyer, $logout);
        $authManager->addChild($buyer, $profile);
        $authManager->addChild($buyer, $guest);

        //Поставщик
        $authManager->addChild($supplier, $update);
        $authManager->addChild($supplier, $create);
        $authManager->addChild($supplier, $logout);
        $authManager->addChild($supplier, $profile);
        $authManager->addChild($supplier, $guest);

        //Admin
        $authManager->addChild($admin, $delete);
        $authManager->addChild($admin, $test);

        $authManager->addChild($admin, $buyer);
        $authManager->addChild($admin, $supplier);

        //Добавляем правило, запрещающее редактировать чужой профиль
        $userProfileOwnerRule = new UserProfileOwnerRule();
        $authManager->add($userProfileOwnerRule);

        $updateOwnProfile = $authManager->createPermission('updateOwnProfile');
        $updateOwnProfile->ruleName = $userProfileOwnerRule->name;
        $authManager->add($updateOwnProfile);

        $authManager->addChild($buyer, $updateOwnProfile);
        $authManager->addChild($supplier, $updateOwnProfile);


    }
}