CREATE TABLE apdo_test_tree (
    id integer NOT NULL AUTO_INCREMENT,
    name varchar(20),

    PRIMARY KEY (id)
);

CREATE TABLE apdo_test_tree_extra (
    id integer NOT NULL AUTO_INCREMENT,
    height integer,
    tree_id integer NOT NULL,

    FOREIGN KEY (tree_id) REFERENCES apdo_test_tree(id),
    UNIQUE KEY (tree_id),
    PRIMARY KEY (id)
);

CREATE TABLE apdo_test_fruit (
    id integer NOT NULL AUTO_INCREMENT,
    name varchar(20) NOT NULL,
    color varchar(5),
    tree_id integer,

    FOREIGN KEY (tree_id) REFERENCES apdo_test_tree(id),
    PRIMARY KEY (id)
);

