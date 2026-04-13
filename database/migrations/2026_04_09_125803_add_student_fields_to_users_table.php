<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'student_id')) {
                $table->string('student_id')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('users', 'program')) {
                $table->string('program')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'year_level')) {
                $table->string('year_level')->nullable()->after('program');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('year_level');
            }
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('address');
            }
            if (!Schema::hasColumn('users', 'gender')) {
                $table->enum('gender', ['Male', 'Female', 'Other'])->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('users', 'guardian_name')) {
                $table->string('guardian_name')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('users', 'guardian_relationship')) {
                $table->string('guardian_relationship')->nullable()->after('guardian_name');
            }
            if (!Schema::hasColumn('users', 'guardian_contact')) {
                $table->string('guardian_contact')->nullable()->after('guardian_relationship');
            }
            if (!Schema::hasColumn('users', 'guardian_email')) {
                $table->string('guardian_email')->nullable()->after('guardian_contact');
            }
            if (!Schema::hasColumn('users', 'guardian_address')) {
                $table->text('guardian_address')->nullable()->after('guardian_email');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['student_id', 'program', 'year_level', 'address', 'date_of_birth', 'gender', 
                       'guardian_name', 'guardian_relationship', 'guardian_contact', 'guardian_email', 'guardian_address'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};