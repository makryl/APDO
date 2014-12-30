
-- ALTER TABLE apdo_test_fruit DROP treeeee;

-- DROP TABLE apdo_test_drop;

-- ALTER TABLE apdo_test_tree MODIFY name varchar(20) COMMENT 'Name';

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

ALTER TABLE apdo_test_fruit ADD tree integer COMMENT 'Tree';

ALTER TABLE apdo_test_fruit ADD FOREIGN KEY (tree) REFERENCES apdo_test_tree(id);
