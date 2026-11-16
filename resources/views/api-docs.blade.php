<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaan Maps API - Documentación Oficial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        kaan: {
                            dark: '#4a0000',
                            primary: '#600000',
                            light: '#7b1f1f',
                            accent: '#009688',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Fira+Code:wght@400;500&display=swap');
        body { font-family: 'Inter', sans-serif; }
        code, pre { font-family: 'Fira Code', monospace; }
        
        /* Estilos personalizados para los métodos HTTP */
        .method-get { background-color: #10B981; color: white; }
        .method-post { background-color: #3B82F6; color: white; }
        .method-put { background-color: #F59E0B; color: white; }
        .method-delete { background-color: #EF4444; color: white; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 flex h-screen overflow-hidden">

    <!-- Sidebar de Navegación -->
    <aside class="w-72 bg-gradient-to-b from-kaan-dark via-kaan-primary to-kaan-light text-white flex flex-col h-full shadow-2xl z-10">
        <div class="p-6 border-b border-white/10 flex items-center space-x-3">
            <!-- Icono representativo -->
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <div>
                <h1 class="text-xl font-bold tracking-tight">Ka'an Maps API</h1>
                <p class="text-xs text-red-200">Versión 1.0.0</p>
            </div>
        </div>
        
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            <a href="#introduccion" class="block px-4 py-2.5 text-sm font-medium text-red-100 hover:bg-white/10 hover:text-white rounded-lg transition-colors">Introducción</a>
            <a href="#autenticacion" class="block px-4 py-2.5 text-sm font-medium text-red-100 hover:bg-white/10 hover:text-white rounded-lg transition-colors">Autenticación</a>
            
            <div class="mt-6">
                <h3 class="px-4 text-xs font-bold text-red-300 uppercase tracking-wider mb-2">Endpoints</h3>
                <a href="#productores" class="block px-4 py-2.5 text-sm font-medium text-red-100 hover:bg-white/10 hover:text-white rounded-lg transition-colors">Productores</a>
                <a href="#productos" class="block px-4 py-2.5 text-sm font-medium text-red-100 hover:bg-white/10 hover:text-white rounded-lg transition-colors">Productos</a>
                <a href="#categorias" class="block px-4 py-2.5 text-sm font-medium text-red-100 hover:bg-white/10 hover:text-white rounded-lg transition-colors">Categorías</a>
                <a href="#eventos" class="block px-4 py-2.5 text-sm font-medium text-red-100 hover:bg-white/10 hover:text-white rounded-lg transition-colors">Eventos</a>
                <a href="#convocatorias" class="block px-4 py-2.5 text-sm font-medium text-red-100 hover:bg-white/10 hover:text-white rounded-lg transition-colors">Convocatorias</a>
            </div>
        </nav>
        
        <div class="p-4 border-t border-white/10 text-xs text-red-200/70 text-center">
            <p>&copy; 2026 Ka'an Maps.</p>
            <p class="mt-1">Desarrollo e Innovación Tecnológica.</p>
        </div>
    </aside>

    <!-- Contenido Principal -->
    <main class="flex-1 overflow-y-auto p-8 lg:p-12 scroll-smooth">
        <div class="max-w-4xl mx-auto space-y-12">

            <!-- Introducción -->
            <section id="introduccion">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-3xl font-bold text-kaan-primary mb-4">Documentación de la API</h2>
                    <p class="text-gray-600 leading-relaxed mb-6 text-lg">
                        Bienvenido a la API RESTful oficial de <strong>Ka'an Maps</strong>. Este ecosistema permite a la aplicación móvil y a otros servicios autorizados interactuar de forma segura con la base de datos centralizada de productores, productos, eventos y programas sociales del estado de Quintana Roo.
                    </p>
                    <div class="bg-red-50 border-l-4 border-kaan-primary p-4 rounded-r-lg">
                        <p class="text-sm text-kaan-dark font-medium">URL Base del Servidor:</p>
                        <code class="text-kaan-primary bg-red-100 px-2 py-1 rounded mt-1 inline-block font-semibold">{{ rtrim(config('app.url'), '/') }}/v1</code>
                    </div>
                </div>
            </section>

            <!-- Autenticación -->
            <section id="autenticacion" class="pt-8">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-2xl font-bold text-kaan-primary mb-4">Autenticación (Sanctum)</h2>
                    <p class="text-gray-600 mb-6">La API está protegida mediante tokens de acceso (Bearer Tokens). Para acceder a los endpoints protegidos, debes iniciar sesión y adjuntar el token generado en los encabezados de tu petición.</p>
                    
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 overflow-x-auto shadow-inner">
                        <pre class="text-green-400 text-sm"><code>Authorization: Bearer {tu_token_aqui}</code></pre>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2">Iniciar Sesión</h3>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="p-4 border-b border-gray-200 flex items-center space-x-3 bg-gray-50">
                            <span class="method-post px-3 py-1 text-xs font-bold rounded-md shadow-sm">POST</span>
                            <code class="text-sm font-bold text-gray-800">/v1/login</code>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-gray-600 mb-4">Genera un nuevo token de acceso. Requerido para realizar operaciones de escritura (POST, PUT, DELETE).</p>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Ejemplo de Cuerpo (JSON):</p>
                            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-gray-300 text-sm"><code>{
    "email": "correoprueba@gmail.com",
    "password": "tu_contraseña_segura",
    "device_name": "android-app"
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Productores -->
            <section id="productores" class="pt-8">
                <h2 class="text-2xl font-bold text-kaan-primary mb-4 px-2">Productores</h2>
                
                <!-- Listar Productores -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                    <div class="p-4 border-b border-gray-200 flex items-center space-x-3 bg-gray-50">
                        <span class="method-get px-3 py-1 text-xs font-bold rounded-md shadow-sm">GET</span>
                        <code class="text-sm font-bold text-gray-800">/v1/productores</code>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 mb-4">Obtiene la lista paginada de productores activos con sus ubicaciones e imágenes de instalaciones. En <code>GET /v1/productores/{id}</code> admite <code>?include=productos</code>.</p>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Parámetros de Consulta Opcionales:</p>
                        <ul class="text-sm text-gray-700 space-y-2 bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <li><code class="bg-white border border-gray-200 px-2 py-0.5 rounded shadow-sm text-kaan-primary">q</code> - Buscar por nombre de negocio o descripción (alias legacy: <code>search</code>).</li>
                            <li><code class="bg-white border border-gray-200 px-2 py-0.5 rounded shadow-sm text-kaan-primary">categoria_id</code> - Filtra por categoría (incluye descendientes; coincide por categoría principal o por productos).</li>
                            <li><code class="bg-white border border-gray-200 px-2 py-0.5 rounded shadow-sm text-kaan-primary">municipio</code> - Ej: Solidaridad, Othón P. Blanco.</li>
                            <li><code class="bg-white border border-gray-200 px-2 py-0.5 rounded shadow-sm text-kaan-primary">estado_verificacion</code> - <code>Pendiente</code>, <code>Verificado</code> o <code>Rechazado</code>.</li>
                            <li><code class="bg-white border border-gray-200 px-2 py-0.5 rounded shadow-sm text-kaan-primary">per_page</code> - Resultados por página (default 15, máximo 100).</li>
                        </ul>
                    </div>
                </div>

                <!-- Crear Productor -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 flex items-center space-x-3 bg-gray-50">
                        <span class="method-post px-3 py-1 text-xs font-bold rounded-md shadow-sm">POST</span>
                        <code class="text-sm font-bold text-gray-800">/v1/productores</code>
                        <span class="text-xs text-kaan-primary border border-red-200 bg-red-50 px-2 py-1 rounded-full ml-auto font-semibold">Requiere Token</span>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 mb-4">Registra un nuevo productor. Acepta <code>multipart/form-data</code> para logo e <code>imagenes_instalaciones[]</code> (campos Pascal_Snake o alias snake_case).</p>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Campos Requeridos:</p>
                        <ul class="text-sm text-gray-700 space-y-2">
                            <li><code class="bg-gray-100 px-2 py-0.5 rounded">ID_Usuario</code> / <code class="bg-gray-100 px-2 py-0.5 rounded">usuario_id</code> - ID del usuario existente.</li>
                            <li><code class="bg-gray-100 px-2 py-0.5 rounded">Nombre_Negocio</code> / <code class="bg-gray-100 px-2 py-0.5 rounded">nombre_negocio</code> - Nombre comercial.</li>
                            <li><code class="bg-gray-100 px-2 py-0.5 rounded">categoria_id</code> / <code class="bg-gray-100 px-2 py-0.5 rounded">categoria_principal_id</code> - Categoría principal (o <code>Tipo_Actividad</code>, deprecado).</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Productos -->
            <section id="productos" class="pt-8">
                <h2 class="text-2xl font-bold text-kaan-primary mb-4 px-2">Productos</h2>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                    <div class="p-4 border-b border-gray-200 flex items-center space-x-3 bg-gray-50">
                        <span class="method-get px-3 py-1 text-xs font-bold rounded-md shadow-sm">GET</span>
                        <code class="text-sm font-bold text-gray-800">/v1/productos</code>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 mb-4">Obtiene el catálogo paginado de productos con jerarquía calculada (<code>categoria_raiz_id</code>, <code>subcategoria_id</code>, <code>tipo_id</code>). En detalle: <code>GET /v1/productos/{id}</code>.</p>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Parámetros de Consulta Opcionales:</p>
                        <ul class="text-sm text-gray-700 space-y-2 bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <li><code class="bg-white border border-gray-200 px-2 py-0.5 rounded shadow-sm text-kaan-primary">categoria_id</code> - Filtra por nodo de categoría incluyendo descendientes.</li>
                            <li><code class="bg-white border border-gray-200 px-2 py-0.5 rounded shadow-sm text-kaan-primary">productor_id</code> - Filtra por productor.</li>
                            <li><code class="bg-white border border-gray-200 px-2 py-0.5 rounded shadow-sm text-kaan-primary">disponible</code> - <code>true</code> / <code>false</code> (o <code>1</code> / <code>0</code>).</li>
                            <li><code class="bg-white border border-gray-200 px-2 py-0.5 rounded shadow-sm text-kaan-primary">etiqueta</code> - Coincidencia exacta en etiquetas JSON.</li>
                            <li><code class="bg-white border border-gray-200 px-2 py-0.5 rounded shadow-sm text-kaan-primary">q</code> - Búsqueda por nombre o descripción.</li>
                            <li><code class="bg-white border border-gray-200 px-2 py-0.5 rounded shadow-sm text-kaan-primary">sort</code> - <code>recientes</code> (default), <code>precio_asc</code>, <code>precio_desc</code>.</li>
                            <li><code class="bg-white border border-gray-200 px-2 py-0.5 rounded shadow-sm text-kaan-primary">per_page</code> - Resultados por página (default 15, máximo 100).</li>
                        </ul>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 flex items-center space-x-3 bg-gray-50">
                        <span class="method-post px-3 py-1 text-xs font-bold rounded-md shadow-sm">POST</span>
                        <code class="text-sm font-bold text-gray-800">/v1/productos</code>
                        <span class="text-xs text-kaan-primary border border-red-200 bg-red-50 px-2 py-1 rounded-full ml-auto font-semibold">Requiere Token</span>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600">Crea un nuevo producto. Acepta <code>multipart/form-data</code> para subir imágenes en el arreglo <code>URLs_Imagenes[]</code>.</p>
                    </div>
                </div>
            </section>

            <!-- Categorías, Eventos y Convocatorias -->
            <section class="pt-8 space-y-6">
                <div id="categorias" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 flex items-center space-x-3 bg-gray-50">
                        <span class="method-get px-3 py-1 text-xs font-bold rounded-md shadow-sm">GET</span>
                        <code class="text-sm font-bold text-gray-800">/v1/categorias</code>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 mb-3">Obtiene la lista plana de categorías activas (3 niveles: Categoría → Subcategoría → Tipo) con <code>Cache-Control</code> y <code>ETag</code>.</p>
                        <ul class="text-sm text-gray-700 space-y-2 bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <li><code class="bg-white border border-gray-200 px-2 py-0.5 rounded shadow-sm text-kaan-primary">nivel</code> - Nivel jerárquico (<code>1</code>, <code>2</code> o <code>3</code>).</li>
                            <li><code class="bg-white border border-gray-200 px-2 py-0.5 rounded shadow-sm text-kaan-primary">padre_id</code> - ID del nodo padre directo.</li>
                        </ul>
                    </div>
                </div>

                <div id="eventos" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 flex items-center space-x-3 bg-gray-50">
                        <span class="method-get px-3 py-1 text-xs font-bold rounded-md shadow-sm">GET</span>
                        <code class="text-sm font-bold text-gray-800">/v1/eventos</code>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600">Lista los eventos y ferias activas programadas.</p>
                    </div>
                </div>

                <div id="convocatorias" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 flex items-center space-x-3 bg-gray-50">
                        <span class="method-get px-3 py-1 text-xs font-bold rounded-md shadow-sm">GET</span>
                        <code class="text-sm font-bold text-gray-800">/v1/convocatorias</code>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600">Lista las convocatorias de programas sociales y apoyos económicos vigentes.</p>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <footer class="pt-12 pb-8 text-center text-sm text-gray-400">
                <p>Ecosistema Digital Ka'an Maps - Gobierno de Quintana Roo</p>
            </footer>

        </div>
    </main>
</body>
</html>