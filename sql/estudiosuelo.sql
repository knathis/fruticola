create table estudiosuelo(
    id   serial primary key not null,
    codigo_laboratorio      varchar(20) not null unique,
    fecha_llegada           date not null,
    fecha_entrega           date not null,
    nombre_usuario          varchar(200) not null,
    cedula                  varchar(50) not null,
    direccion               varchar(200) not null,
    telefono                varchar(100) not null,
    email                   varchar(100) not null,
    --departamento            varchar(100) not null,
    municipio_id            integer not null references municipio(id),
    vereda                  varchar(100) not null,
    finca                   varchar(100) not null,
    altura                  double precision,
    cultivo                 varchar(100) not null,
    estado                  varchar(100) not null,
    tiempo_establecido      varchar(100) not null,
    identificacion_muestra  varchar(20) not null,
    profundidad             double precision,
    topografia              varchar(100) not null,
    superficie              double precision,
    drenaje                 varchar(100) not null,
    riesgo                  varchar(100) not null,
    fertilizantes           varchar(100) not null,
    ultimo_cultivo          varchar(100) not null,
    rendimiento             varchar(100) not null,
    textura_tacto           varchar(100) not null,
    ph_agua_suelo           double precision,               
    materia_organica        double precision,                  
    fosforo                 double precision,         
    azufre                  double precision,        
    acidez                  double precision,        
    aluminio                double precision,          
    calcio                  double precision,        
    magnesio                double precision,          
    potasio                 double precision,         
    sodio                   double precision,       
    cica                    double precision,      
    cice                    double precision,      
    conductividad_electrica double precision,                         
    hierro                  double precision,        
    cobre                   double precision,       
    manganeso               double precision,           
    zinc                    double precision,      
    boro                    double precision      
);


create table ruat_estudiosuelo(
    id serial primary key  not null,
    ruat_id    integer unique not null references ruat(id),
    estudio_id integer not null references estudiosuelo(id)
);