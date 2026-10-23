# Guía operativa de AURA Store

Guía de trabajo para operar la instancia local. AURA confirmó que todavía no hay staging; la tienda usa datos de prueba. Revisa el catálogo y las políticas antes de publicar productos o compartirla con clientes.

## Entrar al panel

1. Abre `/admin` en el ambiente que te comparta el equipo técnico e inicia sesión con tu usuario.
2. Confirma el correo y activa la autenticación de dos factores desde **Seguridad**. El panel no permite operar hasta confirmar 2FA.
3. Usa la cuenta solo para el trabajo asignado. Los roles habituales son **Catálogo**, **Inventario**, **Pedidos** y **Administrador**; cada uno muestra las secciones que necesita.

## Revisar un producto antes de publicarlo

1. En **Productos**, abre el borrador. Completa nombre, categoría, descripción, material, cuidados, precio y guía de tallas aprobados.
2. En **Colores de producto**, relaciona el color con su producto y carga la foto de respaldo si se necesita. En **Galería de fotos**, agrega cada foto como un elemento, escribe su texto alternativo y arrastra los elementos para ordenar la galería.
3. En **Variantes de producto**, verifica que cada combinación de color y talla tenga el SKU correcto y esté activa. Una variante nueva comienza con cero existencias; las variantes existentes se ajustan desde **Importar / exportar inventario** para conservar la bitácora.
4. Comprueba precio, promoción, etiqueta y vista pública. Guarda como **Borrador** mientras falte aprobación; cambia a **Publicado** solo cuando AURA haya validado todos los datos.

El catálogo importado está en borrador y sin stock. Las recomendaciones de talla de demostración de E2E no son una guía comercial aprobada. No ingreses gramajes, rangos de talla, fotos faltantes ni precios estimados como si fueran datos confirmados.

## Actualizar existencias

En **Operaciones → Importar / exportar inventario**, descarga el CSV para obtener los SKU actuales. El encabezado debe ser exactamente `sku,stock`; cada fila lleva un SKU único y una existencia entera igual o mayor a cero.

La importación establece el total de cada variante, no suma la cantidad del archivo al stock actual. Cada diferencia queda registrada con motivo y usuario. Si un SKU no existe o el archivo tiene errores, la importación completa se cancela. Revisa el archivo antes de subirlo.

## Preparar la revisión del catálogo

- [Plantilla de inventario](PLANTILLA-INVENTARIO-AURA.csv): contiene los 81 SKU de las variantes en borrador y el encabezado que acepta el importador. El campo `stock` se deja vacío intencionalmente. Completa todas las filas con un conteo físico confirmado antes de importar; no subas la plantilla sin llenar.
- [Plantilla de revisión de productos](PLANTILLA-REVISION-CATALOGO-AURA.csv): una fila por cada uno de los 19 productos en borrador, con los valores heredados para compararlos y columnas para GSM, descripción, cuidados, fotos adicionales, precio confirmado y aprobación de publicación.

Las plantillas omiten el producto activo de prueba E2E. Los nombres, descripciones y precios heredados sirven como referencia; no significan que AURA los haya aprobado para publicación.

## Atender pedidos de WhatsApp

1. En **Pedidos**, abre los pedidos en estado **Nuevo** y confirma con el cliente los datos, el pago y la entrega.
2. Usa **Confirmar pedido** cuando el cliente acepte. En ese momento se descuentan existencias.
3. En pedidos de WhatsApp, usa **Marcar pagado** únicamente después de verificar el pago por el canal acordado; agrega la referencia.
4. **Marcar enviado** cambia el estado a Enviado y crea un envío de prueba con el proveedor mock. No compra una guía, no imprime etiqueta y el rastreo no es real. Mantén el pedido en Confirmado o Pagado hasta que exista un proceso real de paquetería aprobado.
5. Marca **Entregado** después de confirmar la recepción. Si se cancela antes de enviarse, la acción de cancelar devuelve el stock descontado al confirmar.

Un pedido que lleva más de 48 horas en **Nuevo** genera un recordatorio al correo configurado para pedidos. Para recibirlo, deben estar configurados el correo y el worker de colas.

## Administrar clientes y cuentas

El rol **Administrador** puede crear y editar registros de clientes. Para asociar pedidos anteriores a una cuenta, selecciona una cuenta con correo verificado y confirma por un canal independiente que la persona controla esa cuenta. El rol **Pedidos** puede consultar clientes, pero no vincular cuentas.

## Funciones en modo de prueba

- **Pagos:** el driver mock no cobra. Los pedidos de WhatsApp pueden marcarse como pagados manualmente después de confirmar el pago fuera del sitio.
- **Prueba de pago en línea:** en local, confirma un pedido, inicia su checkout mock desde la cuenta del cliente y usa **Simular pago mock** en el panel. La acción deja el pedido pagado solo para probar el flujo; no confirma transferencias ni realiza cobros.
- **Prueba de reembolso:** sobre el pedido con pago simulado, usa **Registrar reembolso mock** e indica el importe en pesos. Puedes registrar varios reembolsos parciales, hasta el total; son apuntes de prueba y no devuelven dinero.
- **Envíos:** la cotización y el rastreo son simulados. No se emiten guías reales.
- **Datos de catálogo:** existencias, gramajes, descripciones, fotos, kit, políticas y rangos de tallas necesitan aprobación de AURA.
- **Vincular una cuenta a pedidos previos:** un administrador puede asociar el cliente a una cuenta con correo verificado. Confirma por un canal independiente que la persona controla esa cuenta antes de guardar; después podrá consultar los pedidos de ese registro.
- **Correo y respaldos:** para staging/producción se necesitan SMTP, worker, almacenamiento de respaldo externo y una prueba de restauración.

## Capacitación propuesta

Sesión de 45 minutos en local: (1) acceso, roles y 2FA; (2) revisión de producto y publicación; (3) actualización de existencias; (4) ciclo de pedido con pagos y envíos mock; (5) preguntas y práctica con un pedido de prueba. Antes del lanzamiento se repetirá en staging. No uses datos personales reales durante la práctica.
