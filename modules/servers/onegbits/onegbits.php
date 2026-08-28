<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }

require_once __DIR__ . '/lib/ApiClient.php';
require_once __DIR__ . '/lib/Helper.php';

use OneGbits\ApiClient;
use OneGbits\Helper;

/**
 * Module metadata
 */
function onegbits_MetaData()
{
    return [
        'DisplayName' => '1Gbits Dedicated Server (Reseller)',
        'RequiresServer' => true,
        'APIVersion' => '1.1',
        'DefaultNonSSLPort' => '80',
        'DefaultSSLPort' => '443',
    ];
}

/**
 * Product-level config options
 */
function onegbits_ConfigOptions()
{
    return [
        'API Base URL' => [
            'Type' => 'text',
            'Size' => '50',
            'Default' => 'https://api.1gbits.com/v1',
            'Description' => 'Base endpoint for 1Gbits reseller API',
        ],
        'API Key' => [
            'Type' => 'password',
            'Size' => '50',
            'Description' => 'Your reseller API key',
        ],
        'API Secret' => [
            'Type' => 'password',
            'Size' => '50',
            'Description' => 'Your reseller API secret (HMAC)',
        ],
        'Plan SKU' => [
            'Type' => 'text',
            'Size' => '25',
            'Default' => 'DEDI-INTEL-E3',
            'Description' => 'Dedicated server plan identifier',
        ],
        'Location' => [
            'Type' => 'dropdown',
            'Options' => 'ams,lon,nyc,ist,tyo',
            'Description' => 'Datacenter code',
            'Default' => 'ist',
        ],
        'OS Template' => [
            'Type' => 'dropdown',
            'Options' => 'ubuntu-22.04,ubuntu-24.04,debian-12,alma-9,win-2022',
            'Default' => 'ubuntu-24.04',
        ],
        'Auto Assign IP' => [
            'Type' => 'yesno',
            'Description' => 'Let API allocate an IP automatically',
            'Default' => 'on',
        ],
    ];
}

/**
 * Create (Provision)
 */
function onegbits_CreateAccount(array $params)
{
    try {
        $api = Helper::clientFromParams($params);

        $payload = [
            'planSku'   => $params['configoption4'],
            'location'  => $params['configoption5'],
            'os'        => $params['configoption6'],
            'autoIp'    => (bool)$params['configoption7'],
            'hostname'  => $params['domain'] ?: Helper::generateHostname($params),
            'termMonths'=> Helper::termMonthsFromBillingCycle($params['billingcycle']),
            'user'      => [
                'email' => $params['clientsdetails']['email'],
                'firstName' => $params['clientsdetails']['firstname'],
                'lastName' => $params['clientsdetails']['lastname'],
            ],
            'options'   => [
                'raid' => Helper::customField($params, 'RAID Level'),
                'ipCount' => (int) Helper::customField($params, 'Additional IPs'),
                'notes' => Helper::customField($params, 'Order Notes'),
            ],
        ];

        $res = $api->post('/reseller/orders', $payload);
        logModuleCall('onegbits', 'CreateAccount', $payload, $res, '', []);

        if (!$res['success']) {
            return 'API error: ' . Helper::stringify($res);
        }

        // If your API returns serverId, you can persist it here using WHMCS localAPI.
        // Example:
        // $serverId = $res['data']['serverId'] ?? null;
        // if ($serverId) {
        //     localAPI('UpdateClientProduct', [
        //         'serviceid' => $params['serviceid'],
        //         'customfields' => base64_encode(serialize(['Server ID' => $serverId])),
        //     ]);
        // }

        return 'success';
    } catch (\Throwable $e) {
        return 'Create failed: ' . $e->getMessage();
    }
}

/** Suspend */
function onegbits_SuspendAccount(array $params)
{
    try {
        $api = Helper::clientFromParams($params);
        $serverId = Helper::serviceServerId($params);
        if (!$serverId) { return 'Missing Server ID in service custom field'; }
        $res = $api->post("/reseller/servers/{$serverId}/suspend", []);
        logModuleCall('onegbits', 'SuspendAccount', ['serverId'=>$serverId], $res, '', []);
        return $res['success'] ? 'success' : ('API error: ' . Helper::stringify($res));
    } catch (\Throwable $e) {
        return 'Suspend failed: ' . $e->getMessage();
    }
}

/** Unsuspend */
function onegbits_UnsuspendAccount(array $params)
{
    try {
        $api = Helper::clientFromParams($params);
        $serverId = Helper::serviceServerId($params);
        if (!$serverId) { return 'Missing Server ID in service custom field'; }
        $res = $api->post("/reseller/servers/{$serverId}/unsuspend", []);
        logModuleCall('onegbits', 'UnsuspendAccount', ['serverId'=>$serverId], $res, '', []);
        return $res['success'] ? 'success' : ('API error: ' . Helper::stringify($res));
    } catch (\Throwable $e) {
        return 'Unsuspend failed: ' . $e->getMessage();
    }
}

/** Terminate */
function onegbits_TerminateAccount(array $params)
{
    try {
        $api = Helper::clientFromParams($params);
        $serverId = Helper::serviceServerId($params);
        if (!$serverId) { return 'Missing Server ID in service custom field'; }
        $res = $api->delete("/reseller/servers/{$serverId}");
        logModuleCall('onegbits', 'TerminateAccount', ['serverId'=>$serverId], $res, '', []);
        return $res['success'] ? 'success' : ('API error: ' . Helper::stringify($res));
    } catch (\Throwable $e) {
        return 'Terminate failed: ' . $e->getMessage();
    }
}

/** Change Package */
function onegbits_ChangePackage(array $params)
{
    try {
        $api = Helper::clientFromParams($params);
        $serverId = Helper::serviceServerId($params);
        if (!$serverId) { return 'Missing Server ID in service custom field'; }
        $payload = [
            'planSku' => $params['configoption4'],
        ];
        $res = $api->post("/reseller/servers/{$serverId}/change-plan", $payload);
        logModuleCall('onegbits', 'ChangePackage', $payload + ['serverId'=>$serverId], $res, '', []);
        return $res['success'] ? 'success' : ('API error: ' . Helper::stringify($res));
    } catch (\Throwable $e) {
        return 'ChangePackage failed: ' . $e->getMessage();
    }
}

/** Not applicable for baremetal */
function onegbits_ChangePassword(array $params)
{
    return 'Function not supported for this product';
}

/** Admin tab fields */
function onegbits_AdminServicesTabFields(array $params)
{
    $fields = [];
    try {
        $api = Helper::clientFromParams($params);
        $serverId = Helper::serviceServerId($params);
        if ($serverId) {
            $res = $api->get("/reseller/servers/{$serverId}");
            logModuleCall('onegbits', 'AdminServicesTabFields', ['serverId'=>$serverId], $res, '', []);
            if ($res['success'] && isset($res['data'])) {
                $d = $res['data'];
                $fields['Server ID'] = Helper::safe($d['id'] ?? '');
                $fields['Status'] = Helper::safe($d['status'] ?? '');
                $fields['Hostname'] = Helper::safe($d['hostname'] ?? '');
                $fields['Location'] = Helper::safe($d['location'] ?? '');
                $fields['IP Address'] = Helper::safe($d['primaryIp'] ?? '');
                $fields['Plan'] = Helper::safe($d['planSku'] ?? '');
                $fields['OS'] = Helper::safe($d['os'] ?? '');
            } else {
                $fields['Notice'] = 'No data returned from API';
            }
        } else {
            $fields['Notice'] = 'Server not yet provisioned (no Server ID).';
        }
    } catch (\Throwable $e) {
        $fields['Error'] = $e->getMessage();
    }
    return $fields;
}

/** Client Area */
function onegbits_ClientArea(array $params)
{
    $vars = [
        'status' => 'Pending',
        'serverId' => null,
        'details' => [],
        'error' => null,
    ];

    try {
        $api = Helper::clientFromParams($params);
        $serverId = Helper::serviceServerId($params);
        if ($serverId) {
            $res = $api->get("/reseller/servers/{$serverId}");
            logModuleCall('onegbits', 'ClientArea', ['serverId'=>$serverId], $res, '', []);
            if ($res['success']) {
                $vars['status'] = $res['data']['status'] ?? 'Unknown';
                $vars['serverId'] = $serverId;
                $vars['details'] = $res['data'];
            } else {
                $vars['error'] = 'API error while loading details';
            }
        }
    } catch (\Throwable $e) {
        $vars['error'] = $e->getMessage();
    }

    return [
        'tabOverviewReplacementTemplate' => 'clientarea.tpl',
        'templateVariables' => $vars,
    ];
}

/** Test Connection */
function onegbits_TestConnection(array $params)
{
    try {
        $api = Helper::clientFromParams($params);
        $res = $api->get('/reseller/ping');
        logModuleCall('onegbits', 'TestConnection', [], $res, '', []);
        if ($res['success']) {
            return [ 'success' => true, 'error' => '' ];
        }
        return [ 'success' => false, 'error' => 'Ping failed: ' . Helper::stringify($res) ];
    } catch (\Throwable $e) {
        return [ 'success' => false, 'error' => $e->getMessage() ];
    }
}
