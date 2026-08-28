<div class="panel panel-default">
  <div class="panel-heading">Dedicated Server Status</div>
  <table class="table">
    <tr><td>Status</td><td>{$status}</td></tr>
    <tr><td>Server ID</td><td>{$serverId}</td></tr>
    <tr><td>Hostname</td><td>{$details.hostname}</td></tr>
    <tr><td>Location</td><td>{$details.location}</td></tr>
    <tr><td>IP Address</td><td>{$details.primaryIp}</td></tr>
  </table>
  {if $error}
    <div class="alert alert-danger">{$error}</div>
  {/if}
</div>
