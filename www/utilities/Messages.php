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
    public static function notFound(): string
    {
        return "No se encontro";
    }
    public static function bodyWasEmpty(): string
    {
        return "Payload invalido, el cuerpo no puede estar vacio";
    }
    public static function missingParameter(string $name): string
    {
        return sprintf("Falta un parametro requerido: %s", $name);
    }
    public static function wrongLoginInfo(): string
    {
        return "Datos incorrectos";
    }
}

?>
