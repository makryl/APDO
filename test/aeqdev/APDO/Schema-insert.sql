INSERT INTO apdo_test_tree (id, name) VALUES (1, 'apple tree');
INSERT INTO apdo_test_tree (id, name) VALUES (2, 'orange tree');

INSERT INTO apdo_test_tree_extra (id, height, tree_id) VALUES (1, 10, 1);
INSERT INTO apdo_test_tree_extra (id, height, tree_id) VALUES (2, 20, 2);

INSERT INTO apdo_test_fruit (id, name, tree_id) VALUES (1, 'apple1', 1);
INSERT INTO apdo_test_fruit (id, name, tree_id) VALUES (2, 'apple2', 1);
INSERT INTO apdo_test_fruit (id, name, tree_id) VALUES (3, 'orange', 2);
