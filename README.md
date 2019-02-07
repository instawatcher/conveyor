# conveyor

This module is part of the Instawatcher project. It provides the core workers access to Instagram's servers through [mgp25's amazing API](https://github.com/mgp25/Instagram-API).

# :warning: SECURITY WARNING! :warning:

This program exposes your ENTIRE Instagram account to potential threats.
By default, it runs on port 8000, _binds to all interfaces_ and uses **no encryption** (plain HTTP).
Isolating this daemon from the open web is a **MUST**.

# Usage

1. Run `composer install`
2. Copy `.env.example` to `.env` and set your account details
3. Run `php server.php [host:port]` (The default is `0.0.0.0:8000`)