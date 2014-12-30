
CREATE TABLE apdo_test_tree (
    id integer NOT NULL AUTO_INCREMENT,
    name varchar(50) COMMENT 'Name',

    PRIMARY KEY (id)
);

CREATE TABLE apdo_test_fruit (
    id integer NOT NULL AUTO_INCREMENT,
    name varchar(20) NOT NULL COMMENT 'Name',
    color varchar(5) COMMENT 'Color',
    treeeee integer COMMENT 'Tree',

    FOREIGN KEY (treeeee) REFERENCES apdo_test_tree(id),
    PRIMARY KEY (id)
);

CREATE TABLE apdo_test_drop (
    id integer NOT NULL AUTO_INCREMENT,

    PRIMARY KEY (id)
);
