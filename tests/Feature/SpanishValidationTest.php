<?php

test('los mensajes generales de validación se muestran en español', function () {
    expect(__('validation.required', ['attribute' => 'nombre']))
        ->toBe('El campo nombre es obligatorio.')
        ->and(__('validation.unique', ['attribute' => 'correo electrónico']))
        ->toBe('El valor del campo correo electrónico ya está registrado.')
        ->and(__('validation.custom.email.unique'))
        ->toBe('El correo electrónico ya está registrado.');
});
