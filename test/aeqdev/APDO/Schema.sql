
CREATE TABLE apdo_test_tree (
    id integer NOT NULL AUTO_INCREMENT,
    name varchar(20) COMMENT 'Name',

    PRIMARY KEY (id)
) COMMENT 'Tree';

CREATE TABLE apdo_test_tree_extra (
    id integer NOT NULL AUTO_INCREMENT,
    height integer COMMENT 'Height',
    tree integer NOT NULL COMMENT 'Tree',
    parent integer COMMENT 'Parent tree',

    FOREIGN KEY (parent) REFERENCES apdo_test_tree(id),
    FOREIGN KEY (tree) REFERENCES apdo_test_tree(id),
    UNIQUE KEY (tree),
    PRIMARY KEY (id)
) COMMENT 'Tree extra';

CREATE TABLE apdo_test_fruit (
    id integer NOT NULL AUTO_INCREMENT,
    name varchar(20) NOT NULL COMMENT 'Name',
    color varchar(5) COMMENT 'Color',
    tree integer COMMENT 'Tree',

    FOREIGN KEY (tree) REFERENCES apdo_test_tree(id),
    PRIMARY KEY (id)
) COMMENT 'Fruit';
