-- ALTER TABLE apdo_test_tree_extra DROP heightttt;

-- ALTER TABLE apdo_test_fruit DROP treeeee_id;

-- DROP TABLE apdo_test_drop;

-- ALTER TABLE apdo_test_tree MODIFY name varchar(20);

ALTER TABLE apdo_test_tree_extra ADD height integer;

ALTER TABLE apdo_test_fruit ADD tree_id integer;

ALTER TABLE apdo_test_fruit ADD FOREIGN KEY (tree_id) REFERENCES apdo_test_tree(id);

