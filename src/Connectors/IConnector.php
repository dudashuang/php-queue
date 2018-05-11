<?php
namespace Lily\Connectors;

interface IConnector {
    public static function get_connection($options = []);
}
