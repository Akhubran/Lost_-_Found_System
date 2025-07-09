$result = mysqli_query($conn, "SELECT * FROM items WHERE type='lost'");
echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Status</th><th>Action</th></tr>";
while ($item = mysqli_fetch_assoc($result)) {
    echo "<tr>
            <td>{$item['id']}</td>
            <td>{$item['item_name']}</td>
            <td>{$item['status']}</td>
            <td>
              <a href='edit_item.php?id={$item['id']}'>Edit</a> |
              <a href='delete_item.php?id={$item['id']}'>Delete</a>
            </td>
          </tr>";
}
