<?php

namespace Xpressengine\Plugins\MultiLanguageManage;

use Route;
use Xpressengine\Plugin\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    /**
     * 이 메소드는 활성화(activate) 된 플러그인이 부트될 때 항상 실행됩니다.
     *
     * @return void
     */
    public function boot()
    {
        $this->importSettingMenu();
        $this->route();
    }

    protected function importSettingMenu()
    {
        app('xe.register')->push(
            'settings/menu',
            'lang.multi_language_manage_index',
            [
                'title' => 'multi_language_manage::title',
                'description' => 'multi_language_manage::desc',
                'display' => true,
                'ordering' => 200
            ]
        );
    }

    protected function route()
    {
        Route::settings(Plugin::getId(), function () {
            Route::get('/', [
                'as' => 'multi_language_manage::index',
                'uses' => 'MultiLanguageManageController@index',
                'settings_menu' => 'lang.multi_language_manage_index'
            ]);

            Route::get('/import', [
                'as' => 'multi_language_manage::get_import',
                'uses' => 'MultiLanguageManageController@getImport'
            ]);
            Route::post('/upload', [
                'as' => 'multi_language_manage::post_import',
                'uses' => 'MultiLanguageManageController@postImport'
            ]);

            Route::get('/export', [
                'as' => 'multi_language_manage::export',
                'uses' => 'MultiLanguageManageController@export'
            ]);
        }, ['namespace' => 'Xpressengine\\Plugins\\MultiLanguageManage\\Controllers']);
    }

    /**
     * 플러그인이 활성화될 때 실행할 코드를 여기에 작성한다.
     *
     * @param string|null $installedVersion 현재 XpressEngine에 설치된 플러그인의 버전정보
     *
     * @return void
     */
    public function activate($installedVersion = null)
    {
        $folderPath = 'app/public/multi_language_manage';
        if (file_exists(storage_path($folderPath)) == false) {
            mkdir(storage_path($folderPath), 0777, true);
        }
    }

    /**
     * 플러그인을 설치한다. 플러그인이 설치될 때 실행할 코드를 여기에 작성한다
     *
     * @return void
     */
    public function install()
    {
        // implement code
    }

    /**
     * 해당 플러그인이 설치된 상태라면 true, 설치되어있지 않다면 false를 반환한다.
     * 이 메소드를 구현하지 않았다면 기본적으로 설치된 상태(true)를 반환한다.
     *
     * @return boolean 플러그인의 설치 유무
     */
    public function checkInstalled()
    {
        // implement code

        return parent::checkInstalled();
    }

    /**
     * 플러그인을 업데이트한다.
     *
     * @return void
     */
    public function update()
    {
        // implement code
    }

    /**
     * 해당 플러그인이 최신 상태로 업데이트가 된 상태라면 true, 업데이트가 필요한 상태라면 false를 반환함.
     * 이 메소드를 구현하지 않았다면 기본적으로 최신업데이트 상태임(true)을 반환함.
     *
     * @return boolean 플러그인의 설치 유무,
     */
    public function checkUpdated()
    {
        // implement code

        return parent::checkUpdated();
    }
}
