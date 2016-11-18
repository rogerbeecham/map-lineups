# We need to re-sample each condition where data are from above and below. 
tmp0.4_grid_above <- data %>% filter(base == 0.4, geography == "1_grid", approach == "above", accuracy > 0.55)
tmp0.4_grid_above <- tmp0.4_grid_above[sample(nrow(tmp0.4_grid_above), 15),]
tmp0.4_grid_below <- data %>% filter(base == 0.4, geography == "1_grid", approach == "below", accuracy > 0.55)
tmp0.4_grid_below <- tmp0.4_grid_below[sample(nrow(tmp0.4_grid_below), 15),]

tmp0.5_grid_above <- data %>% filter(base == 0.5, geography == "1_grid", approach == "above", accuracy > 0.55)
tmp0.5_grid_above <- tmp0.5_grid_above[sample(nrow(tmp0.5_grid_above), 15),]
tmp0.5_grid_below <- data %>% filter(base == 0.5, geography == "1_grid", approach == "below", accuracy > 0.55)
tmp0.5_grid_below <- tmp0.5_grid_below[sample(nrow(tmp0.5_grid_below), 15),]

tmp0.6_grid_above <- data %>% filter(base == 0.6, geography == "1_grid",approach == "above", accuracy > 0.55)
tmp0.6_grid_above <- tmp0.6_grid_above[sample(nrow(tmp0.6_grid_above), 15),]
tmp0.6_grid_below <- data %>% filter(base == 0.6, geography == "1_grid", approach == "below", accuracy > 0.55)
tmp0.6_grid_below <- tmp0.6_grid_below[sample(nrow(tmp0.6_grid_below), 15),]

tmp0.4_reg_above <- data %>% filter(base == 0.4, geography == "2_reg", approach == "above", accuracy > 0.55)
tmp0.4_reg_above <- tmp0.4_reg_above[sample(nrow(tmp0.4_reg_above), 15),]
tmp0.4_reg_below <- data %>% filter(base == 0.4, geography == "2_reg", approach == "below", accuracy > 0.55)
tmp0.4_reg_below <- tmp0.4_reg_below[sample(nrow(tmp0.4_reg_below), 15),]

tmp0.5_reg_above <- data %>% filter(base == 0.5, geography == "2_reg", approach == "above", accuracy > 0.55)
tmp0.5_reg_above <- tmp0.5_reg_above[sample(nrow(tmp0.5_reg_above), 15),]
tmp0.5_reg_below <- data %>% filter(base == 0.5, geography == "2_reg", approach == "below", accuracy > 0.55)
tmp0.5_reg_below <- tmp0.5_reg_below[sample(nrow(tmp0.5_reg_below), 15),]

tmp0.6_reg_above <- data %>% filter(base == 0.6, geography == "2_reg",  approach == "above", accuracy > 0.55)
tmp0.6_reg_above <- tmp0.6_reg_above[sample(nrow(tmp0.6_reg_above), 15),]
tmp0.6_reg_below <- data %>% filter(base == 0.6, geography == "2_reg",  approach == "below", accuracy > 0.55)
tmp0.6_reg_below <- tmp0.6_reg_below[sample(nrow(tmp0.6_reg_below), 15),]

tmp0.4_irreg_above <- data %>% filter(base == 0.4, geography == "3_irreg",approach == "above", accuracy > 0.55)
tmp0.4_irreg_above <- tmp0.4_irreg_above[sample(nrow(tmp0.4_irreg_above), 15),]
tmp0.4_irreg_below <- data %>% filter(base == 0.4, geography == "3_irreg",approach == "below", accuracy > 0.55)
tmp0.4_irreg_below <- tmp0.4_irreg_below[sample(nrow(tmp0.4_irreg_below), 15),]

tmp0.5_irreg_above <- data %>% filter(base == 0.5, geography == "3_irreg", approach == "above", accuracy > 0.55)
tmp0.5_irreg_above <- tmp0.5_irreg_above[sample(nrow(tmp0.5_irreg_above), 15),]
tmp0.5_irreg_below <- data %>% filter(base == 0.5, geography == "3_irreg", approach == "below", accuracy > 0.55)
tmp0.5_irreg_below <- tmp0.5_irreg_below[sample(nrow(tmp0.5_irreg_below), 15),]

tmp0.6_irreg_above <- data %>% filter(base == 0.6, geography == "3_irreg", approach == "above", accuracy > 0.55)
tmp0.6_irreg_above <- tmp0.6_irreg_above[sample(nrow(tmp0.6_irreg_above), 15),]
tmp0.6_irreg_below <- data %>% filter(base == 0.6, geography == "3_irreg", approach == "below", accuracy > 0.55)
tmp0.6_irreg_below <- tmp0.6_irreg_below[sample(nrow(tmp0.6_irreg_below), 15),]

samlped_midbases<-rbind(tmp0.4_grid_above, tmp0.4_grid_below, 
                        tmp0.5_grid_above, tmp0.5_grid_below,
                        tmp0.6_grid_above, tmp0.6_grid_below,
                        
                        tmp0.4_reg_above, tmp0.4_reg_below,
                        tmp0.5_reg_above, tmp0.5_reg_below,
                        tmp0.6_reg_above, tmp0.6_reg_below,
                        
                        tmp0.4_irreg_above, tmp0.4_irreg_below,
                        tmp0.5_irreg_above, tmp0.5_irreg_below,
                        tmp0.6_irreg_above, tmp0.6_irreg_below)

data_model<- data %>% filter(base < 0.4 & approach == "above" | base > 0.6 & approach == "below")
data_model<-rbind(data_model, samlped_midbases)

rm(tmp0.4_grid_above, tmp0.4_grid_below, tmp0.4_irreg_above, tmp0.4_irreg_below, tmp0.4_reg_above, tmp0.4_reg_below)
rm(tmp0.5_grid_above, tmp0.5_grid_below, tmp0.5_irreg_above, tmp0.5_irreg_below, tmp0.5_reg_above, tmp0.5_reg_below)
rm(tmp0.6_grid_above, tmp0.6_grid_below, tmp0.6_irreg_above, tmp0.6_irreg_below, tmp0.6_reg_above, tmp0.6_reg_below)