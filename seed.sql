create database facturascripts;
use facturascripts;
create table proveedores (
	acreedor int not null,
	cifnif varchar(20) not null,
	codcliente varchar(20),
	codimpuestoportes varchar(20),
	codpago varchar(20),
	codproveedor varchar(20) primary key,
	codretencion varchar(20),
	codserie varchar(20),
	codsubcuenta varchar(20),
	debaja int not null default 0,
	email varchar(100),
	fax varchar(50),
	fechaalta date not null,
	fechabaja date,
	idcontacto int not null default 1,
	langcode varchar(10) not null default 'es_ES',
	nombre varchar(100) not null,
	observaciones text,
	personafisica int not null default 0,
	razonsocial varchar(100) not null,
	regimeniva varchar(50) not null default 'General',
	telefono1 varchar(50),
	telefono2 varchar(50),
	tipoidfiscal varchar(10) not null default 'NIF',
	web varchar(100)
);

INSERT INTO facturascripts.proveedores (acreedor,cifnif,codcliente,codimpuestoportes,codpago,codproveedor,codretencion,codserie,codsubcuenta,debaja,email,fax,fechaalta,fechabaja,idcontacto,langcode,nombre,observaciones,personafisica,razonsocial,regimeniva,telefono1,telefono2,tipoidfiscal,web) VALUES
	 (0,'',NULL,'IVA21',NULL,'1',NULL,NULL,'',0,'','','2025-06-03',NULL,1,'es_ES','prueba','',1,'prueba','General','','','NIF','')