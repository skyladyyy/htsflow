<?php
/*
 * Copyright 2015-2016 Fondazione Istituto Italiano di Tecnologia.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

$querySpec = "SELECT * FROM peak_calling WHERE secondary_id=" . $row["id"] . ";";
$resSpec = mysqli_query($con, $querySpec);
?>

<table>
<thead>
	<tr>
		<th>ID PEAK</th>
		<th>INPUT</th>
		<th>CHIP</th>
		<th>LABEL</th>
		<th>EXP NAME</th>
		<th>METHOD</th>
	</tr>
</thead>
<tbody>
<?php
    while ($rowSpec = mysqli_fetch_assoc($resSpec)) {
        ?><tr>
		<td><?php echo $rowSpec["id"]; ?></td>
		<td><?php echo $rowSpec["input_id"]; ?><a href="primary-browse.php?primaryId=<?php echo $rowSpec["input_id"]; ?>"><i class="fa fa-reply"></i></a></td>
		<td><?php echo $rowSpec["primary_id"]; ?><a href="primary-browse.php?primaryId=<?php echo $rowSpec["primary_id"]; ?>"><i class="fa fa-reply"></i></a></td>
		<td><?php echo $rowSpec["label"]; ?></td>
		<td><?php echo $rowSpec["exp_name"]; ?></td>
		<td><?php echo $rowSpec["program"]; ?></td>
	</tr><?php
    }
?></tbody>
</table>