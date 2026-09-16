<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
SELECT 
    o.id,
    oi.quantity,
    o.total_amount,
    o.order_status,
    o.created_at,
    p.title,
    p.image
FROM orders o
JOIN order_items oi ON o.id = oi.order_id
JOIN products p ON oi.product_id = p.id
WHERE o.user_id = ?
ORDER BY o.created_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Orders</title>
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, Helvetica, sans-serif;
        }

        body{
            background:#f5f7fb;
            padding:40px 5%;
        }

        .my-listings-container{
            max-width:1200px;
            margin:auto;
        }

        .my-listings-container h1{
            font-size:36px;
            margin-bottom:30px;
            color:#222;
        }

        .product-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
            gap:25px;
        }

        .product-card{
            background:white;
            border-radius:20px;
            overflow:hidden;
            box-shadow:0 5px 20px rgba(0,0,0,0.08);
            transition:0.3s;
        }

        .product-card:hover{
            transform:translateY(-5px);
        }

        .product-card img{
            width:100%;
            height:220px;
            object-fit:cover;
            background:#f0f0f0;
        }

        .product-content{
            padding:20px;
        }

        .product-content h3{
            margin-bottom:12px;
            color:#222;
            font-size:20px;
        }

        .price{
            color:#0057ff;
            font-size:24px;
            font-weight:bold;
            margin-bottom:12px;
        }

        .product-content p{
            margin-bottom:8px;
            color:#555;
        }

        .date{
            margin-top:15px;
            color:#888;
            font-size:14px;
        }

        .status-pending{
            color:#ff9800;
            font-weight:bold;
        }

        .status-processing{
            color:#2196f3;
            font-weight:bold;
        }

        .status-completed{
            color:#28a745;
            font-weight:bold;
        }

        .status-cancelled{
            color:#dc3545;
            font-weight:bold;
        }

        .empty-orders{
            background:white;
            padding:40px;
            text-align:center;
            border-radius:20px;
            box-shadow:0 5px 20px rgba(0,0,0,0.08);
        }

        .back-home{
            display:inline-block;
            margin-bottom:25px;
            text-decoration:none;
            background:#0057ff;
            color:white;
            padding:12px 20px;
            border-radius:10px;
            font-weight:600;
            transition:0.3s;
        }

        .back-home:hover{
            background:#0037a3;
        }

        @media(max-width:768px){
            .my-listings-container h1{
                font-size:28px;
            }

            .product-grid{
                grid-template-columns:1fr;
            }
        }
    </style>
</head>
<body>

<div class="my-listings-container">

    <a href="index.php" class="back-home">
        < Back to Home
    </a>

    <h1>My Orders</h1>

    <div class="product-grid">

        <?php while($row = $result->fetch_assoc()) { ?>

        <div class="product-card">

            <img src="uploads/<?php echo $row['image']; ?>">

            <div class="product-content">

                <h3><?php echo htmlspecialchars($row['title']); ?></h3>

                <p class="price">R<?php echo number_format($row['total_amount'], 2); ?></p>

                <p><strong>Qty:</strong> <?php echo $row['quantity']; ?></p>

                <p>
                    <strong>Status:</strong>
                    <span class="status-<?php echo strtolower($row['order_status']); ?>">
                        <?php echo htmlspecialchars($row['order_status']); ?>
                    </span>
                </p>

                <p class="date">
                    <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                </p>

            </div>

        </div>

        <?php } ?>

    </div>

</div>

</body>
</html>