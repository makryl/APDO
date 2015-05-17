
-- ALTER TABLE apdo_test_fruit DROP treeeee;

-- DROP TABLE apdo_test_drop;

-- ALTER TABLE apdo_test_tree MODIFY name varchar(20); -- Name

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

ALTER TABLE apdo_test_fruit ADD tree integer; -- Tree

ALTER TABLE apdo_test_fruit ADD FOREIGN KEY (tree) REFERENCES apdo_test_tree(id);
