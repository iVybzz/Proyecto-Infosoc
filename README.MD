# CÓMO ACCEDER AL ENTORNO BDD

## Estructura
- `Frontend/`: interfaz web, desplegada en Vercel
- `Backend/`: API en PHP, alojada en InfinityFree

## Configuración Local del Backend
1. Copia `Backend/CONFIG/config.example.php` y renómbralo a `Backend/CONFIG/config.php`.
2. Completa los datos de la base de datos (host, nombre, usuario y contraseña).
3. Pide las credenciales reales al zaldi **por dm**. Nunca las subas a GitHub.

`config.php` está en el `.gitignore`, por lo que Git no lo sube.

## Acceso al entorno en la nube
- **Frontend:** https://proyecto-infosoc.vercel.app
- **Backend:** https://base-ong.kesug.com
- **Hosting y base de datos:** panel de InfinityFree (cuenta administrada por zaldi).
- Las credenciales las gestiona el zaldi y se comparten por dm.

## Probar la conexión a la base de datos
1. Sube `config.php` al servidor junto a `conexion.php`.
2. Si la conexión falla, el usuario ve un mensaje genérico y el detalle queda en `Backend/logs/error.log`.