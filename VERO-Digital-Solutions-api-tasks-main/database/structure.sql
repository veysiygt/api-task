create table if not exists construction_stages
(
    ID           integer primary key,
    name         varchar(255)              not null,
    start_date   datetime                  not null,
    end_date     datetime,
    duration     float,
    durationUnit varchar(50),
    color        varchar(50),
    externalId   nvarchar(255),
    status       varchar(50) default 'NEW' not null
)