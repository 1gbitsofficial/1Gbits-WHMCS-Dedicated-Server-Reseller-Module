{if $error}
    <div class="alert alert-danger">{$error|escape:'html'}</div>
{/if}

<div class="card panel panel-default">
    <div class="card-header panel-heading">
        <h3 class="card-title panel-title">Dedicated Server Details</h3>
    </div>
    <div class="card-body panel-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <tbody>
                <tr>
                    <th scope="row" class="w-25">Status</th>
                    <td>{$status|escape:'html'}</td>
                </tr>
                <tr>
                    <th scope="row">Server ID</th>
                    <td>{if $serverId}{$serverId|escape:'html'}{else}&mdash;{/if}</td>
                </tr>
                <tr>
                    <th scope="row">Hostname</th>
                    <td>{if $details.hostname}{$details.hostname|escape:'html'}{else}&mdash;{/if}</td>
                </tr>
                <tr>
                    <th scope="row">Location</th>
                    <td>{if $details.location}{$details.location|escape:'html'}{else}&mdash;{/if}</td>
                </tr>
                <tr>
                    <th scope="row">IP Address</th>
                    <td>{if $details.primaryIp}{$details.primaryIp|escape:'html'}{else}&mdash;{/if}</td>
                </tr>
                <tr>
                    <th scope="row">Plan</th>
                    <td>{if $details.planSku}{$details.planSku|escape:'html'}{else}&mdash;{/if}</td>
                </tr>
                <tr>
                    <th scope="row">Operating System</th>
                    <td>{if $details.os}{$details.os|escape:'html'}{else}&mdash;{/if}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
