<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                $this->addIndexIfMissing($table, 'leads', ['created_at'], 'idx_leads_created_at');
                $this->addIndexIfMissing($table, 'leads', ['status_id', 'created_at'], 'idx_leads_status_created');
                $this->addIndexIfMissing($table, 'leads', ['source_id', 'created_at'], 'idx_leads_source_created');
                $this->addIndexIfMissing($table, 'leads', ['assigned_to', 'created_at'], 'idx_leads_assigned_created');
                $this->addIndexIfMissing($table, 'leads', ['created_by', 'created_at'], 'idx_leads_creator_created');
            });
        }

        if (Schema::hasTable('lead_activities')) {
            Schema::table('lead_activities', function (Blueprint $table) {
                $this->addIndexIfMissing($table, 'lead_activities', ['lead_id', 'id', 'expected_value'], 'idx_lead_activities_lead_id_expected');
                $this->addIndexIfMissing($table, 'lead_activities', ['lead_id', 'created_at', 'id'], 'idx_lead_activities_lead_created_id');
                $this->addIndexIfMissing($table, 'lead_activities', ['lead_id', 'next_followup'], 'idx_lead_activities_lead_followup');
                $this->addIndexIfMissing($table, 'lead_activities', ['activity_type_id', 'lead_id'], 'idx_lead_activities_type_lead');
            });
        }

        if (Schema::hasTable('lead_sources')) {
            Schema::table('lead_sources', function (Blueprint $table) {
                $this->addIndexIfMissing($table, 'lead_sources', ['status', 'name'], 'idx_lead_sources_status_name');
            });
        }

        if (Schema::hasTable('lead_statuses')) {
            Schema::table('lead_statuses', function (Blueprint $table) {
                $this->addIndexIfMissing($table, 'lead_statuses', ['name', 'sort_order'], 'idx_lead_statuses_name_sort');
            });
        }

        if (Schema::hasTable('lead_activity_types')) {
            Schema::table('lead_activity_types', function (Blueprint $table) {
                $this->addIndexIfMissing($table, 'lead_activity_types', ['status', 'title'], 'idx_lead_activity_types_status_title');
            });
        }

        if (Schema::hasTable('lead_activity_sub_statuses')) {
            Schema::table('lead_activity_sub_statuses', function (Blueprint $table) {
                $this->addIndexIfMissing($table, 'lead_activity_sub_statuses', ['status', 'title'], 'idx_lead_activity_sub_statuses_status_title');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $this->addIndexIfMissing($table, 'users', ['user_type', 'name'], 'idx_users_type_name');
            });
        }

        if (Schema::hasTable('staff')) {
            Schema::table('staff', function (Blueprint $table) {
                $this->addIndexIfMissing($table, 'staff', ['user_id'], 'idx_staff_user_id');
            });
        }

        if (Schema::hasTable('tickets')) {
            Schema::table('tickets', function (Blueprint $table) {
                $this->addIndexIfMissing($table, 'tickets', ['viewed'], 'idx_tickets_viewed');
            });
        }

        if (Schema::hasTable('conversations')) {
            Schema::table('conversations', function (Blueprint $table) {
                $this->addIndexIfMissing($table, 'conversations', ['receiver_id', 'receiver_viewed'], 'idx_conversations_receiver_viewed');
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $this->addIndexIfMissing($table, 'notifications', ['notifiable_type', 'notifiable_id', 'read_at', 'created_at'], 'idx_notifications_user_unread_created');
                $this->addIndexIfMissing($table, 'notifications', ['notifiable_type', 'notifiable_id', 'read_at', 'type', 'created_at'], 'idx_notifications_user_unread_type_created');
            });
        }

        if (Schema::hasTable('notification_types')) {
            Schema::table('notification_types', function (Blueprint $table) {
                $this->addIndexIfMissing($table, 'notification_types', ['type'], 'idx_notification_types_type');
            });
        }

        if (Schema::hasTable('notification_type_translations')) {
            Schema::table('notification_type_translations', function (Blueprint $table) {
                $this->addIndexIfMissing($table, 'notification_type_translations', ['notification_type_id', 'lang'], 'idx_notification_type_translations_type_lang');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_type_translations')) {
            Schema::table('notification_type_translations', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'notification_type_translations', 'idx_notification_type_translations_type_lang');
            });
        }

        if (Schema::hasTable('notification_types')) {
            Schema::table('notification_types', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'notification_types', 'idx_notification_types_type');
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'notifications', 'idx_notifications_user_unread_type_created');
                $this->dropIndexIfExists($table, 'notifications', 'idx_notifications_user_unread_created');
            });
        }

        if (Schema::hasTable('lead_activities')) {
            Schema::table('lead_activities', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'lead_activities', 'idx_lead_activities_type_lead');
                $this->dropIndexIfExists($table, 'lead_activities', 'idx_lead_activities_lead_followup');
                $this->dropIndexIfExists($table, 'lead_activities', 'idx_lead_activities_lead_created_id');
                $this->dropIndexIfExists($table, 'lead_activities', 'idx_lead_activities_lead_id_expected');
            });
        }

        if (Schema::hasTable('conversations')) {
            Schema::table('conversations', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'conversations', 'idx_conversations_receiver_viewed');
            });
        }

        if (Schema::hasTable('tickets')) {
            Schema::table('tickets', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'tickets', 'idx_tickets_viewed');
            });
        }

        if (Schema::hasTable('staff')) {
            Schema::table('staff', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'staff', 'idx_staff_user_id');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'users', 'idx_users_type_name');
            });
        }

        if (Schema::hasTable('lead_activity_sub_statuses')) {
            Schema::table('lead_activity_sub_statuses', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'lead_activity_sub_statuses', 'idx_lead_activity_sub_statuses_status_title');
            });
        }

        if (Schema::hasTable('lead_activity_types')) {
            Schema::table('lead_activity_types', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'lead_activity_types', 'idx_lead_activity_types_status_title');
            });
        }

        if (Schema::hasTable('lead_statuses')) {
            Schema::table('lead_statuses', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'lead_statuses', 'idx_lead_statuses_name_sort');
            });
        }

        if (Schema::hasTable('lead_sources')) {
            Schema::table('lead_sources', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'lead_sources', 'idx_lead_sources_status_name');
            });
        }

        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'leads', 'idx_leads_creator_created');
                $this->dropIndexIfExists($table, 'leads', 'idx_leads_assigned_created');
                $this->dropIndexIfExists($table, 'leads', 'idx_leads_source_created');
                $this->dropIndexIfExists($table, 'leads', 'idx_leads_status_created');
                $this->dropIndexIfExists($table, 'leads', 'idx_leads_created_at');
            });
        }
    }

    private function addIndexIfMissing(Blueprint $table, string $tableName, array $columns, string $indexName): void
    {
        if ($this->tableHasColumns($tableName, $columns) && !Schema::hasIndex($tableName, $indexName)) {
            $table->index($columns, $indexName);
        }
    }

    private function dropIndexIfExists(Blueprint $table, string $tableName, string $indexName): void
    {
        if (Schema::hasIndex($tableName, $indexName)) {
            $table->dropIndex($indexName);
        }
    }

    private function tableHasColumns(string $tableName, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($tableName, $column)) {
                return false;
            }
        }

        return true;
    }
};
