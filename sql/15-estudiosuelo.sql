create table estudiosuelo(
    id   serial primary key not null,
    codigo_laboratorio      varchar(20) not null unique,
    fecha_llegada           date,
    fecha_entrega           date,
    nombre_usuario          varchar(200),
    cedula                  varchar(50),
    direccion               varchar(200),
    telefono                varchar(100),
    email                   varchar(100),
    --departamento            varchar(100) not null,
    municipio_id            integer references municipio(id),
    vereda                  varchar(100),
    finca                   varchar(100),
    altura                  double precision,
    cultivo                 varchar(100),
    estado                  varchar(100),
    tiempo_establecido      varchar(100),
    identificacion_muestra  varchar(20),
    profundidad             double precision,
    topografia              varchar(100),
    superficie              double precision,
    drenaje                 varchar(100),
    riesgo                  varchar(100),
    fertilizantes           varchar(100),
    ultimo_cultivo          varchar(100),
    rendimiento             varchar(100),
    textura_tacto           varchar(100),
    interp_textura          varchar(50),
    ph_agua_suelo           double precision,
    interp_ph               varchar(50),               
    materia_organica        double precision,
    interp_materia          varchar(50),                  
    fosforo                 double precision,         
    interp_fosforo          varchar(50),
    azufre                  double precision,        
    interp_azufre           varchar(50),
    acidez                  double precision,        
    interp_acidez           varchar(50),
    aluminio                double precision,          
    interp_aluminio         varchar(50),
    calcio                  double precision,        
    interp_calcio           varchar(50),
    magnesio                double precision,          
    interp_magnesio         varchar(50),
    potasio                 double precision,         
    interp_potasio          varchar(50),
    sodio                   double precision,       
    interp_sodio            varchar(50),
    cica                    double precision,      
    cice                    double precision,      
    interp_cic              varchar(50),
    conductividad_electrica double precision,                         
    interp_conductividad    varchar(50),
    hierro                  double precision,        
    interp_hierro           varchar(50),
    cobre                   double precision,       
    interp_cobre            varchar(50),
    manganeso               double precision,           
    interp_manganeso        varchar(50),
    zinc                    double precision,      
    interp_zinc             varchar(50),
    boro                    double precision,      
    interp_boro             varchar(50),

    saturacion_calcio                       double precision,
    interp_saturacion_calcio                varchar(50),
    saturacion_magnesio                     double precision,
    interp_saturacion_magnesio              varchar(50),
    saturacion_potasio                      double precision,
    interp_saturacion_potasio               varchar(50),
    saturacion_sodio                        double precision,
    interp_saturacion_sodio                 varchar(50),
    saturacion_aluminio                     double precision,
    interp_saturacion_aluminio              varchar(50),
    relacion_calcio_boro                    double precision,
    interp_relacion_calcio_boro             varchar(50),
    relacion_calcio_magnesio                double precision,
    interp_relacion_calcio_magnesio         varchar(50),
    relacion_magnesio_potasio               double precision,
    interp_relacion_magnesio_potasio        varchar(50),
    relacion_calcio_potasio                 double precision,
    interp_relacion_calcio_potasio          varchar(50),
    relacion_calcio_magnesio_potasio        double precision,
    interp_relacion_calcio_magnesio_potasio varchar(50),

    observacion text
);


create table ruat_estudiosuelo(
    id serial primary key  not null,
    ruat_id    integer unique not null references ruat(id),
    estudio_id integer not null references estudiosuelo(id),
    numero integer not null
);