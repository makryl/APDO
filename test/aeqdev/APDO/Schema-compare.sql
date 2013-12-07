CREATE TABLE apdo_test_tree (
    id integer NOT NULL AUTO_INCREMENT,
    name varchar(50),

    PRIMARY KEY (id)
);

CREATE TABLE apdo_test_tree_extra (
    id integer NOT NULL AUTO_INCREMENT,
    heightttt integer,
    tree_id integer NOT NULL,

    FOREIGN KEY (tree_id) REFERENCES apdo_test_tree(id),
    UNIQUE KEY (tree_id),
    PRIMARY KEY (id)
);

CREATE TABLE apdo_test_fruit (
    id integer NOT NULL AUTO_INCREMENT,
    name varchar(20) NOT NULL,
    color varchar(5),
    treeeee_id integer,

    FOREIGN KEY (treeeee_id) REFERENCES apdo_test_tree(id),
    PRIMARY KEY (id)
);

CREATE TABLE apdo_test_drop (
    id integer NOT NULL AUTO_INCREMENT,

    PRIMARY KEY (id)
);

