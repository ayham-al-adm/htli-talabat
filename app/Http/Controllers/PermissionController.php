<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Access\Role;
use Illuminate\Http\Request;
use App\Models\Access\Permission;
use App\Base\Constants\Auth\Permission as PermissionSlug;

class PermissionController extends Controller
{
    // public function index($id)
    // {
    //     $role = Role::find($id);
    //     $permissions = Permission::all();
    //     $permissionRole = $role->permissions()->get();

    //     return Inertia::render('pages/roles/permission', [
    //         'role' => $role,
    //         'permissions' => $permissions,
    //         'permissionRole' => $permissionRole,
    //     ]);
    // }

    public function index($id)
{
    $role = Role::find($id);
    $currentUserRole = auth()->user()->roles()->first();

    $otherPermissions = [
        PermissionSlug::ADD_SERVICE_LOCATION,
        PermissionSlug::WEB_CREATE_BOOKING,
        PermissionSlug::VIEW_WEB_PROFILE,
        PermissionSlug::VIEW_WEB_HISTORY,
        PermissionSlug::VIEW_WEB_HISTORY_DETAIL ,
        PermissionSlug::VIEW_WEB_SUPPORT,
        PermissionSlug::CREATE_WEB_SUPPORT_TICKET ,
        PermissionSlug::VIEW_WEB_SUPPORT_TICKET_DETAIL ,
    ];

    $groupsToBeExcluded = [
        'dispatcher',
        'chat',
        'owner-management',
        'geo-fencing',
        'loyalty-rewards',
        'referral-management',
        'peak-zone-view',
        'delete-peak-zone',
        'third-party-settings',
        'cms-landing-website',
        'dispatcher dashboard',
        'dispatcher drivers',
        'dispatcher ride',
        'dispatcher ride request',
        'dispatcher ongoing request',
        'dispatcher scheduled rides',
        'dispatcher request enquiry',
        'dispatcher request enquiry booking',
        'dispatcher request enquiry cancel',
        'dispatcher chat',
        'settings',
        'edit_app_modules',
        'delete_app_modules',
        'edit_preference',
        'addons',
        'agent-management',
        'agent dashboard',
        'agent ride',
        'agent ride request',
        'agent ongoing request',
        'agent scheduled rides',
        'agent request enquiry',
        'agent request enquiry booking',
        'agent request enquiry cancel',
        'agent chat',
        'agent wallet',
        'add-wallet-payment',
        'support-management',
    ];

    $permissionsToBeExcluded = [
        'banner_image',
        'add_banner_image',
        'edit_banner_image',
        'delete_banner_image',
        'toggle_banner_image',
        'view-zone',
        'view-zone-map',
        'add-zone',
        'edit-zone',
        'delete-zone',
        'toggle-zone',
        'zone-surge',
        'update-zone-surge',
        'rental-package',
        'add-rental-package',
        'edit-rental-package',
        'delete-rental-package',
        'toggle-rental-package',
        'vehicle-fare',
        'add-package-price',
        'manage-goods-types',
        'add-goods-types',
        'edit-goods-types',
        'delete-goods-types',
        'toggle-goods-types',
        'view-airport',
        'Map-view-Airport',
        'Add-Airport',
        'Edit-airport',
        'Delete-airport',
        'Toggle-airport',
        'view-sos',
        'add-sos',
        'edit-sos',
        'delete-sos',
        'toggle-sos',
        'view-cancellation',
        'add-cancellation',
        'edit-cancellation',
        'delete-cancellation',
        'toggle-cancellation',
        'preference_view',
        'create_preference',
        'toggle_preference',
        'delete_preference',
        'view-approval-pending-drivers',
        'negetive-balance-drivers',
        'withdrawal-request-drivers',
        'delete-request-drivers',
        'manage-driver-needed-document',
        'add-driver-needed-document',
        'edit-driver-needed-document',
        'delete-driver-needed-document',
        'toggle-driver-needed-document',
        'manage-driver-bank-info',
        'toggle-bank-info',
        'manage-subscription',
        'add-subscription',
        'edit-subscription',
        'delete-subscription',
        'toggle-subscription',
        'driver_reupload_bulk_file',
        'owner-report',
        'fleet-report',
        'schedule-delivery-request-view',
        'cancelled-delivery-request-view',
        'bid-ride-settings-view',
        'customization-settings-view',
        'languages',
        'add_languages',
        'delete_languages',
        'browse_languages',
        'bulk_upload',
        'upload_bulk_file',
        'download_bulk_file',
        'reupload_bulk_file',
        '',
        '',
        '',
    ];

    if ($currentUserRole->slug === 'super-admin') {
        // Super admin can see all permissions
        $permissions = Permission::whereNotIn('slug',$otherPermissions )->whereNotIn('slug', $permissionsToBeExcluded)->whereNotIn('main_menu',$groupsToBeExcluded)->get();
    } else {
        // Admins only see their assigned permissions
        $allowedPermissionSlugs = $currentUserRole->permissions()->whereNotIn('slug', $permissionsToBeExcluded)->pluck('slug');
        $permissions = Permission::whereNotIn('slug',$otherPermissions )->whereNotIn('slug', $permissionsToBeExcluded)->whereNotIn('main_menu',$groupsToBeExcluded)->whereIn('slug', $allowedPermissionSlugs)->get();
    }



    $permissionRole = $role->permissions()->whereNotIn('slug', $permissionsToBeExcluded)->get();
    return Inertia::render('pages/roles/permission', [
        'role' => $role,
        'permissions' => $permissions,
        'permissionRole' => $permissionRole,
    ]);
}

    public function store(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);
        $permissions = Permission::whereIn('slug', $request->permissions)->pluck('id');

        $role->permissions()->sync($permissions);


        // return redirect()->route('roles.index');
        return response()->json([
            'message' => __('Permissions synced successfully'),
        ]);
    }


    /**
     * User's permissions
     *
     * */
    // public function userPermissions()
    // {

    //     $role = auth()->user()->roles()->first();
    //     // dd($role);

    //     if($role->slug=='super-admin'){

    //       $permissions = Permission::pluck('slug')->all();

    //     }else{

    //     $permissions = $role->permissions()->pluck('slug')->toArray();

    //     }
    //     // dd($permissions);


    //     return response()->json([
    //         'success'=>true,
    //         'message' => __('Permissions listed successfully'),
    //         'data'=>$permissions
    //     ]);


    // }
    public function userPermissions()
{
    $role = auth()->user()->roles()->first();

    if ($role->slug === 'super-admin') {
        // Super admin can see all permissions
        $otherPermissions = [
            PermissionSlug::WEB_CREATE_BOOKING,
            PermissionSlug::VIEW_WEB_PROFILE,
            PermissionSlug::VIEW_WEB_HISTORY,
            PermissionSlug::VIEW_WEB_HISTORY_DETAIL ,
            PermissionSlug::VIEW_WEB_SUPPORT,
            PermissionSlug::CREATE_WEB_SUPPORT_TICKET ,
            PermissionSlug::VIEW_WEB_SUPPORT_TICKET_DETAIL ,
            PermissionSlug::DISPATHCER_DASHBOARD,
            PermissionSlug::DISPATHCER_DRIVERS,
            PermissionSlug::DISPATHCER_RIDE,
            PermissionSlug::DISPATHCER_RIDE_REQUEST ,
            PermissionSlug::DISPATHCER_RIDE_REQUEST_VIEW ,
            PermissionSlug::DISPATHCER_RIDE_REQUEST_CANCEL ,
            PermissionSlug::DISPATHCER_RIDE_REQUEST_ASSIGN ,
            PermissionSlug::DISPATHCER_ONGOING_REQUEST ,
            PermissionSlug::DISPATHCER_ONGOING_REQUEST_VIEW ,
            PermissionSlug::DISPATHCER_ONGOING_REQUEST_CANCEL ,
            PermissionSlug::DISPATHCER_ONGOING_REQUEST_ASSIGN ,
            PermissionSlug::DISPATHCER_CHAT,
            PermissionSlug::DISPATHCER_SCHEDULED_RIDES,
            PermissionSlug::DISPATHCER_REQUEST_ENQUIRY,
            PermissionSlug::OWNER_BOOKING,
        ];
        $permissions = Permission::whereNotIn('slug',$otherPermissions )->pluck('slug')->all();
        $permissions[] = 'super-admin';
    } else {
        // Other admins see only the permissions assigned to their role
        $permissions = $role->permissions()->pluck('slug')->toArray();
    }

    return response()->json([
        'success' => true,
        'message' => __('Permissions listed successfully'),
        'data' => $permissions
    ]);
}
}
