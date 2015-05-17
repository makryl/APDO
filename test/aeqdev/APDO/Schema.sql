
CREATE TABLE apdo_test_tree -- Tree
(
    id integer NOT NULL AUTO_INCREMENT,
    name varchar(20), -- Name

    PRIMARY KEY (id)
);

CREATE TABLE apdo_test_tree_extra -- Tree extra
(
    id integer NOT NULL AUTO_INCREMENT,
    height integer, -- Height
    tree integer NOT NULL, -- Tree
    parent integer, -- Parent tree

    FOREIGN KEY (parent) REFERENCES apdo_test_tree(id),
    FOREIGN KEY (tree) REFERENCES apdo_test_tree(id),
    UNIQUE KEY (tree),
    PRIMARY KEY (id)
);

CREATE TABLE apdo_test_fruit -- Fruit
(
    id integer NOT NULL AUTO_INCREMENT,
    name varchar(20) NOT NULL, -- Name
    color varchar(5), -- Color
    tree integer, -- Tree

    FOREIGN KEY (tree) REFERENCES apdo_test_tree(id),
    PRIMARY KEY (id)
);
