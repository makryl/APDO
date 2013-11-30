
DROP TABLE IF EXISTS apdo_test_fruit;
DROP TABLE IF EXISTS apdo_test_tree_extra;
DROP TABLE IF EXISTS apdo_test_tree;

CREATE TABLE apdo_test_tree (
    id integer not null auto_increment,
    name character varying (20),

    primary key (id)
);

CREATE TABLE apdo_test_tree_extra (
    id integer not null auto_increment,
    height integer,
    tree_id integer not null,

    unique key (tree_id),
    foreign key (tree_id) references apdo_test_tree(id),
    primary key (id)
);

CREATE TABLE apdo_test_fruit (
    id integer not null auto_increment,
    name character varying (20) not null,
    color character varying (5),
    tree_id integer,

    foreign key (tree_id) references apdo_test_tree(id),
    primary key (id)
);

INSERT INTO apdo_test_tree (id, name) VALUES (1, 'apple tree');
INSERT INTO apdo_test_tree (id, name) VALUES (2, 'orange tree');

INSERT INTO apdo_test_tree_extra (id, height, tree_id) VALUES (1, 10, 1);
INSERT INTO apdo_test_tree_extra (id, height, tree_id) VALUES (2, 20, 2);

INSERT INTO apdo_test_fruit (id, name, tree_id) VALUES (1, 'apple1', 1);
INSERT INTO apdo_test_fruit (id, name, tree_id) VALUES (2, 'apple2', 1);
INSERT INTO apdo_test_fruit (id, name, tree_id) VALUES (3, 'orange', 2);
