<?php
namespace Deployer;


// Set default variables.
set('autossh_socket_path', '/var/run/autossh');
set('autossh_localhost',   'localhost');
set('autossh_log_file',    '/var/log/autossh.log');


// Generate a unique ID for a given ssh tunnel.
function autosshGenerateId($host, $username, $port, $local_port) {
	$hash = md5($host.$username.$port.$local_port);
	return substr($hash, 0, 10); // Returns only the first 10 characters (out of 32) of the hash.
}


// Configure the instance for tunneling via autossh.
desc('Provision autossh');
task('provision:autossh', function () {
	// Ensure autossh is installed.
	if (!commandExist('autossh')) {
		if (askConfirmation('Autossh is not installed. Would you like to install it?')) {
			run('apt-get update && apt-get install -y autossh');

			// Check if the installation was successful.
			if (!commandExist('autossh')) {
				throw error('Failed to install autossh');
			}

			writeln('<info>Autossh installed successfully</info>');
		}
	}

	// Ensure the log directory exists.
	$autossh_log_path = run("dirname {{autossh_log_file}}");
	if (test("[ ! -d {$autossh_log_path} ]")) {
		if (askConfirmation("Autossh log directory \"{$autossh_log_path}\" does not exist. Would you like to create it?")) {
			run("mkdir -p {$autossh_log_path}") || throw error("Failed to create autossh log directory: {$autossh_log_path}");
			writeln("<info>Autossh log directory created: {$autossh_log_path}</info>");
		}
	}

	// Ensure the socket path exists.
	if (test('[ ! -d {{autossh_socket_path}} ]')) {
		if (askConfirmation('Autossh socket directory "{{autossh_socket_path}}" does not exist. Would you like to create it?')) {
			run('mkdir -p {{autossh_socket_path}}') || throw error('Failed to create autossh socket directory: {{autossh_socket_path}}');
			writeln('<info>Autossh socket directory created: {{autossh_socket_path}}</info>');
		}
	}
})->oncePerNode();


// Open ssh tunnels.
desc('Open ssh tunnels');
task('provision:autossh:open', function () {
	if (has('autossh')) {
		invoke('provision:autossh');

		foreach (get('autossh') as $key => $tunnel_config) {
			$localhost   = get('autossh_localhost');
			$log_file    = get('autossh_log_file');
			$socket_path = get('autossh_socket_path');
			$username    = $tunnel_config['username'];
			$host        = $tunnel_config['host'];
			$port        = $tunnel_config['port'];
			$key_file    = $tunnel_config['key_file'];
			$local_port  = $tunnel_config['local_port'];
			$remote_port = $tunnel_config['remote_port'];

			$socket_file = $socket_path.'/'.autosshGenerateId($host, $username, $port, $local_port).'.sock';

			// Check if the tunnel is already open.
			if (!test("autossh -M {$socket_file} -O check {$username}@{$host}")) {
				// Check if the local port is available.
				if (test("echo > /dev/tcp/{$localhost}/{$local_port}")) {
					// Tunnel is not open, local port is available, log and open it.
					writeln("Opening SSH tunnel: {$username}@{$host} (Local Port: {$local_port}, Remote Port: {$remote_port})");
					run("autossh -f -N -L {$local_port}:{$localhost}:{$remote_port} -o \"ControlPath={$socket_file}\" -p {$port} -i {$key_file} {$username}@{$host} >> {$log_file} 2>&1") || throw error('Failed to open SSH tunnel');
					writeln("<info>SSH tunnel opened successfully.</info>");
				} else {
					// Local port is not available, log and abort.
					throw error("Local port {$local_port} is not available for SSH tunnel: {$username}@{$host} (Remote Port: {$remote_port})");
				}
			}
		}
	}
})->oncePerNode();


// Close ssh tunnels.
desc('Close ssh tunnels');
task('provision:autossh:close', function () {
	if (has('autossh')) {
		invoke('provision:autossh');

		foreach (get('autossh') as $key => $tunnel_config) {
			$localhost   = get('autossh_localhost');
			$log_file    = get('autossh_log_file');
			$socket_path = get('autossh_socket_path');
			$username    = $tunnel_config['username'];
			$host        = $tunnel_config['host'];
			$port        = $tunnel_config['port'];
			$key_file    = $tunnel_config['key_file'];
			$local_port  = $tunnel_config['local_port'];
			$remote_port = $tunnel_config['remote_port'];

			$socket_file = $socket_path.'/'.autosshGenerateId($host, $username, $port, $local_port).'.sock';

			// Check if the tunnel is already open.
			if (test("autossh -M {$socket_file} -O check {$username}@{$host}")) {
				// Tunnel is open, log and close it.
				writeln("Closing SSH tunnel: {$username}@{$host} (Local Port: {$local_port}, Remote Port: {$remote_port})") || throw error('Failed to close SSH tunnel');
				run("autossh -M {$socket_file} -O exit {$username}@{$host} >> {$log_file} 2>&1");
				writeln("<info>SSH tunnel closed successfully.</info>");
			}
		}
	}
})->oncePerNode();