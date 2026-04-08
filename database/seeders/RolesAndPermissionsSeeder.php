<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Pazienti
            'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
            'patient_records.view', 'patient_records.create', 'patient_records.edit',
            'patient_consents.manage',
            // Calendario
            'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.delete',
            'availability.manage',
            // Fatturazione
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete',
            'invoices.export_sts',
            // Intervisioni
            'intervisioni.view', 'intervisioni.create', 'intervisioni.edit',
            // Documenti
            'documents.view', 'documents.upload', 'documents.delete',
            // Chat
            'messages.team', 'messages.patient',
            // Workspace
            'social_posts.manage',
            // Admin
            'users.invite', 'users.manage', 'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Admin: accesso totale
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Psicologo
        $psicologo = Role::firstOrCreate(['name' => 'psicologo']);
        $psicologo->givePermissionTo([
            'patients.view', 'patients.create', 'patients.edit',
            'patient_records.view', 'patient_records.create', 'patient_records.edit',
            'patient_consents.manage',
            'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.delete',
            'availability.manage',
            'invoices.view', 'invoices.create', 'invoices.edit',
            'intervisioni.view', 'intervisioni.create', 'intervisioni.edit',
            'documents.view', 'documents.upload',
            'messages.team', 'messages.patient',
        ]);

        // Nutrizionista
        $nutrizionista = Role::firstOrCreate(['name' => 'nutrizionista']);
        $nutrizionista->givePermissionTo([
            'patients.view', 'patients.create', 'patients.edit',
            'patient_records.view', 'patient_records.create', 'patient_records.edit',
            'patient_consents.manage',
            'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.delete',
            'availability.manage',
            'invoices.view', 'invoices.create', 'invoices.edit',
            'intervisioni.view', 'intervisioni.create', 'intervisioni.edit',
            'documents.view', 'documents.upload',
            'messages.team', 'messages.patient',
        ]);

        // Osteopata
        $osteopata = Role::firstOrCreate(['name' => 'osteopata']);
        $osteopata->syncPermissions($nutrizionista->permissions);

        // Collaboratore (sola lettura)
        $collaboratore = Role::firstOrCreate(['name' => 'collaboratore']);
        $collaboratore->givePermissionTo([
            'patients.view', 'patient_records.view',
            'appointments.view', 'documents.view', 'messages.team',
            'intervisioni.view',
        ]);
    }
}
