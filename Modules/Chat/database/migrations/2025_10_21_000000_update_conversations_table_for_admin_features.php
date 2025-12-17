<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Add admin_id if it doesn't exist
            if (!Schema::hasColumn('conversations', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('user_id');
                $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null');
            }
        });

        // Add indexes only if they don't exist
        $this->addIndexIfNotExists('conversations', ['user_id', 'status'], 'conversations_user_id_status_index');
        $this->addIndexIfNotExists('conversations', ['admin_id', 'status'], 'conversations_admin_id_status_index');
        $this->addIndexIfNotExists('conversations', ['status', 'created_at'], 'conversations_status_created_at_index');
        $this->addIndexIfNotExists('conversations', ['admin_id', 'updated_at'], 'conversations_admin_id_updated_at_index');

        Schema::table('messages', function (Blueprint $table) {
            // Add sender_type enum if it doesn't exist
            if (!Schema::hasColumn('messages', 'sender_type')) {
                $table->enum('sender_type', ['user', 'admin', 'super-admin'])->after('sender_id');
            }
        });

        // Add indexes for messages table
        $this->addIndexIfNotExists('messages', ['conversation_id', 'created_at'], 'messages_conversation_id_created_at_index');
        $this->addIndexIfNotExists('messages', ['sender_type', 'created_at'], 'messages_sender_type_created_at_index');
    }

    public function down(): void
    {
        // Drop indexes if they exist
        $this->dropIndexIfExists('conversations', 'conversations_user_id_status_index');
        $this->dropIndexIfExists('conversations', 'conversations_admin_id_status_index');
        $this->dropIndexIfExists('conversations', 'conversations_status_created_at_index');
        $this->dropIndexIfExists('conversations', 'conversations_admin_id_updated_at_index');
        
        $this->dropIndexIfExists('messages', 'messages_conversation_id_created_at_index');
        $this->dropIndexIfExists('messages', 'messages_sender_type_created_at_index');

        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'admin_id')) {
                $table->dropForeign(['admin_id']);
                $table->dropColumn('admin_id');
            }
        });

        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'sender_type')) {
                $table->dropColumn('sender_type');
            }
        });
    }

    private function addIndexIfNotExists($table, $columns, $indexName)
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'");
        
        if (empty($indexes)) {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        }
    }

    private function dropIndexIfExists($table, $indexName)
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'");
        
        if (!empty($indexes)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
};
