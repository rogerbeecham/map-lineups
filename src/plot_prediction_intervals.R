tmp_grid <- data_model  %>% filter(accuracy > 0.55, geography == "1_grid")
prediction_intervals_grid <- predictInterval(merMod = m_grid, newdata = tmp_grid, 
                                           level = 0.9, n.sims = 1000,
                                           stat = "median", type="linear.prediction",
                                           include.resid.var = TRUE)

tmp_grid <- cbind(tmp_grid, prediction_intervals_grid)

# calculate fit line log transformed to base 2
coef <- fixef(m_grid)
fit_slope <- coef[2]/log(2)
fit_intercept <- coef[1]/log(2)

# create plot object with loess regression lines
g_grid <- ggplot(tmp_grid) + 
  stat_smooth(aes(x = base, y = lwr/log(2)), color="#e41a1c", size=0.1,  method = "loess", se = FALSE) +
  stat_smooth(aes(x = base, y = upr/log(2)),  color="#e41a1c", size=0.1, method = "loess", se = FALSE)
# build plot object for rendering 
gg_1 <- ggplot_build(g_grid)
# extract data for the loess lines 
df_2 <- data.frame(x = gg_1$data[[1]]$x,
                  ymin = gg_1$data[[1]]$y,
                  ymax = gg_1$data[[2]]$y) 
# use the loess data to add the 'ribbon' to plot plus regression line 
g_grid <- g_grid +
  geom_ribbon(data = df_2, aes(x = x, ymin = ymin, ymax = ymax),
              fill = "#e41a1c", alpha = 0.1) +
  scale_y_continuous(limits = c(-6, -0.1), labels=anti_log) +
  geom_abline(slope = fit_slope, intercept = fit_intercept, size = 0.5, color = "#e41a1c", linetype = "dashed") +
  geom_point(data = tmp_grid, aes(x = base, y = log(jnd)/log(2)), size = 3, color= "#bdbdbd", alpha = 0.25) +
  labs(x ="Moran's I", y ="jnd") +
  ggtitle("regular grid") +
  theme_bw()

tmp_reg <- data_model %>% filter(accuracy > 0.55, geography == "2_reg")
prediction_intervals_reg <- predictInterval(merMod = m_reg, newdata = tmp_reg, 
                                          level = 0.9, n.sims = 1000,
                                          stat = "median", type = "linear.prediction",
                                          include.resid.var = TRUE)

tmp_reg<-cbind(tmp_reg, prediction_intervals_reg)

coef = fixef(m_reg)
fit_slope = coef[2] / log(2)
fit_intercept = coef[1]/ log(2)

# create plot object with loess regression lines
g_reg <- ggplot(tmp_reg) + 
  stat_smooth(aes(x = base, y = lwr/log(2) ), color = "#377eb8", size = 0.1,  method = "loess", se = FALSE) +
  stat_smooth(aes(x = base, y = upr/log(2)),  color = "#377eb8", size = 0.1, method = "loess", se = FALSE)
# build plot object for rendering 
gg_1 <- ggplot_build(g_reg)
# extract data for the loess lines from the 'data' slot
df_2 <- data.frame(x = gg_1$data[[1]]$x,
                  ymin = gg_1$data[[1]]$y,
                  ymax = gg_1$data[[2]]$y) 
# use the loess data to add the 'ribbon' to plot plus regression line 
g_reg <- g_reg +
  geom_ribbon(data = df_2, aes(x = x, ymin = ymin, ymax = ymax),
              fill = "#377eb8", alpha = 0.1)+
  scale_y_continuous(limits = c(-6, -0.1), labels=anti_log)+
  geom_abline(slope = fit_slope, intercept = fit_intercept, size = 0.5, color = "#377eb8", linetype = "dashed")+
  geom_point(data = tmp_reg,aes(x = base, y = log(jnd)/log(2)), size = 3, color = "#bdbdbd", alpha = 0.25)+
  labs(x = "", y = "")+
  ggtitle("regular real")+
  theme_bw()

tmp_irreg <- data_model %>% filter(accuracy > 0.55, geography == "3_irreg")
prediction_intervals_irreg <- predictInterval(merMod = m.irreg, newdata = tmp_irreg, 
                                            level = 0.95, n.sims = 1000,
                                            stat = "median", type = "linear.prediction",
                                            include.resid.var = TRUE)

tmp_irreg <- cbind(tmp_irreg, prediction_intervals_irreg)

tmp <- rbind(tmp_grid, tmp_reg, tmp_irreg)

coef <- fixef(m.irreg)
fit_slope <- coef[2]/log(2)
fit_intercept <- coef[1]/log(2)

g_irreg <- ggplot(tmp_irreg) + 
  stat_smooth(aes(x = base, y = lwr/log(2)), color = "#4daf4a", size=0.1,  method = "loess", se = FALSE) +
  stat_smooth(aes(x = base, y = upr/log(2)),  color = "#4daf4a", size=0.1, method = "loess", se = FALSE)
# build plot object for rendering 
gg_1 <- ggplot_build(g_irreg)
# extract data for the loess lines from the 'data' slot
df_2 <- data.frame(x = gg_1$data[[1]]$x,
                  ymin = gg_1$data[[1]]$y,
                  ymax = gg_1$data[[2]]$y) 
# use the loess data to add the 'ribbon' to plot plus regression line 
g_irreg <- g_irreg +
  geom_ribbon(data = df_2, aes(x = x, ymin = ymin, ymax = ymax),
              fill = "#4daf4a", alpha = 0.1) +
  scale_y_continuous(limits = c(-6, -0.1), labels = anti_log) +
  geom_abline(slope = fit_slope, intercept = fit_intercept, size = 0.5, color = "#4daf4a", linetype = "dashed") +
  geom_point(data=tmp_irreg,aes(x = base, y=log(jnd)/log(2)), size = 3, color = "#bdbdbd", alpha = 0.25) +
  labs(x = "Moran's I", y = "") +
  ggtitle("irregular real") +
  theme_bw()

grid.arrange(g_grid, g_reg, g_irreg, ncol = 3, nrow = 1)

rm(tmp_grid, tmp_reg, tmp_irreg)