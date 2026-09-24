<?php

abstract class Messages
{
    public static function operationSuccessful(): string
    {
        return "La operacion fue exitosa";
    }

    public static function operationFailed(): string
    {
        return "Ocurrio un error inesperado";
    }
    public static function roleCanBe(): string
    {
        return "Rol solo puede ser ADMIN,STOCK o SALES";
    }
    public static function bodyWasEmpty(): string
    {
        return "Payload invalido, el cuerpo no puede estar vacio";
    }

    public static function doesntExist(string $what): string
    {
        return "El elemento requerido $what no existe";
    }

    public static function missingParameter(string $name): string
    {
        return sprintf("Falta un parametro requerido: %s", $name);
    }
    public static function wrongLoginInfo(): string
    {
        return "Datos incorrectos";
    }
    public static function wrongRole(): string
    {
        return "Rol incorrecto";
    }
}

?>
