# Integraciones futuras: pagos y envíos

## Estado actual

La tienda mantiene `PAYMENT_DRIVER=mock` y `SHIPPING_DRIVER=mock` como valores por defecto. No se hacen cobros ni se compran guías. Un driver distinto al implementado falla al resolverse, así que no puede activarse por accidente con una variable de entorno.

Las reglas de la tienda dependen de `PaymentGateway` y `ShippingProvider`, no de un SDK comercial. Para añadir una integración se crea un adaptador que implementa ese contrato y se registra en `AppServiceProvider`; las credenciales se leen desde variables de entorno y nunca se guardan en el repositorio.

En `local`/`testing` con driver mock, el panel permite simular el pago de un checkout pendiente; la acción exige el total vigente y no está disponible en otros entornos. Esto permite recorrer la administración de pedidos y probar reembolsos. El panel registra reembolsos de prueba con estado `mocked`, referencia y motivo para auditoría, y limita el total reembolsado al importe pagado. Ninguna de estas acciones mueve dinero. El adaptador real deberá extender el flujo para solicitar y reconciliar reembolsos con el proveedor.

## Mercado Pago

Cuando AURA proporcione credenciales sandbox:

1. Crear `MercadoPagoPaymentGateway` e implementar `PaymentGateway::createCheckout()` y `verifyWebhook()`.
2. Crear la preferencia usando el número de pedido como referencia externa y el total recalculado desde la base de datos. No aceptar importes ni estados enviados por el navegador.
3. Verificar la firma del webhook según el protocolo vigente de Mercado Pago, validar referencia e importe contra el pago pendiente y deduplicar eventos por su identificador.
4. Conservar el flujo existente: solo un evento verificado puede marcar un pago como pagado; si el pago local aún no está guardado, la tienda devuelve un error transitorio para que el proveedor reintente sin consumir el ID del evento. La confirmación manual de pedidos WhatsApp permanece separada.
5. Añadir variables para token, secreto de webhook y URL de retorno en `config/integrations.php`; mantener mock como predeterminado y habilitar `mercadopago` solo en sandbox durante la validación.
6. Validar pagos aprobados, rechazados, pendientes, duplicados, importes distintos, firma inválida y reembolsos antes de producción.

No habilitar el driver real hasta que el webhook esté publicado bajo HTTPS y la cuenta, las notificaciones y las credenciales de producción hayan sido aprobadas por AURA.

## Cotización y guías

La paquetería/agregador sigue sin elegir. Antes de desarrollar el adaptador, AURA debe definir cobertura, tarifas, cuenta de origen, quién absorbe ajustes de peso, política de recolección y quién autoriza cancelar una guía.

El adaptador implementará `ShippingProvider::quote()` y `createShipment()`. La respuesta de creación ya transporta proveedor, servicio, rastreo, URL de etiqueta, costo, estado y datos de respuesta; el registro de envío conserva el estado y payload que entregue el driver. La respuesta actual `mock` se produce dentro de `MockShippingProvider`, no en la regla de actualización de pedidos.

Al implementar un proveedor real:

- usar el folio del pedido como clave de idempotencia al crear una guía, porque la llamada externa puede completarse aunque la transacción local falle;
- guardar solo el payload mínimo necesario para operar y dar soporte, sin tokens ni datos de tarjeta;
- probar cotizaciones por CP y peso, creación, etiquetas protegidas, rastreo, cancelación y reintentos en sandbox;
- no marcar como enviado un pedido si el proveedor no confirma la creación de la guía.

Hasta seleccionar proveedor y cerrar esas decisiones, `MockShippingProvider` sigue cubriendo las pruebas del flujo administrativo.
