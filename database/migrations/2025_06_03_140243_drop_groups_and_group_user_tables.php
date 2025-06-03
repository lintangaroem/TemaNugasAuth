<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
        {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropForeign(['group_id']); // Or $table->dropForeign('projects_group_id_foreign');
            });
    
            // Step 2: Now you can drop the 'groups' table3
            Schema::dropIfExists('groups');
    
            // If you also intend to drop 'group_user' table as the migration name suggests
            Schema::dropIfExists('group_user');
        }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
