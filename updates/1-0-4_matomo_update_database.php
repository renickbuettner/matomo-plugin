<?php

use October\Rain\Database\Updates\Migration;
use DB;

return new class extends Migration {
    public function up()
    {
        Db::table('system_settings')
            ->where('item', 'renick_matomo_settings')
            ->update(['value' => DB::raw("REPLACE(value, '\"site_id\":','\"matomo_site_id\":')")]);
    }

    public function down()
    {
        Db::table('system_settings')
            ->where('item', 'renick_matomo_settings')
            ->update(['value' => DB::raw("REPLACE(value, '\"matomo_site_id\":','\"site_id\":')")]);
    }
};
