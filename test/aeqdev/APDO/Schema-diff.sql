-- ALTER TABLE apdo_test_fruit DROP treeeee_id;

-- DROP TABLE apdo_test_drop;

-- ALTER TABLE apdo_test_tree MODIFY name varchar(20);

CREATE TABLE apdo_test_tree_extra (
    id integer NOT NULL AUTO_INCREMENT,
    height integer,
    tree_id integer NOT NULL,

    FOREIGN KEY (tree_id) REFERENCES apdo_test_tree(id),
    UNIQUE KEY (tree_id),
    PRIMARY KEY (id)
);

ALTER TABLE apdo_test_fruit ADD tree_id integer;

ALTER TABLE apdo_test_fruit ADD FOREIGN KEY (tree_id) REFERENCES apdo_test_tree(id);

