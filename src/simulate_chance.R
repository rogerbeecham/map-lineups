simulate.staircase.below<- function(target)
{
  f_stat <- 99
  estimated_jnd <- 0
  start <- 0
  num_steps <- target*20+2
  steps <- data.frame(matrix(ncol = 3, nrow = num_steps))
  colnames(steps) <- c("min","max","jnd")
  condition <- num_steps-2
  for(i in 1:num_steps)
  {
    steps[i,1] <- start
    steps[i,2] <- start+0.01
    steps[i,3] <- round(abs(target-start), 2)
    if(i < condition)
    {
      start <- start+0.05
    }
    else
    {
      start <- start+0.02 
    }
  }

  windows <- data.frame(matrix(ncol = 2, nrow = 24))
  colnames(windows) <- c("window", "jnd") 
  jnd <- 1:50
  jnd[1] <- steps[1,3]
  # starting distance of 0.2 
  if(nrow(steps) < 6)
  {
    row_index <- 1
  }
  else
  {
    row_index <- nrow(steps)-5
  }
  for(j in 1:50)
  {
    answer <- sample(c(1,0), 1)
    if(answer == 1)
    {
      row_index <- row_index+1
      row_index <-min(num_steps, row_index)
      jnd[j+1] <- steps[row_index,3]
    }
    if(answer == 0)
    {
      row_index <-row_index-3
      row_index <-max(1, row_index)
      jnd[j+1] <- steps[row_index,3]
    }
    if(j>23)
    {
      counter <- 0
      window_start <- j-23
      for(k in window_start:j)
      {
        counter <- counter+1
        if(counter < 9)
        {
          windows[counter,1]<- "one"
        }
        else if(counter < 17)
        {
          windows[counter,1]<- "two"
        }
        else
        {
          windows[counter,1]<- "three"
        }
        windows[counter,2] <- jnd[k]
      }
      # f-test 
      pop_mean <- mean(windows$jnd)
      sample_mean_one <- mean(windows[windows$window=="one",]$jnd)
      sample_mean_two <- mean(windows[windows$window=="two",]$jnd)
      sample_mean_three <- mean(windows[windows$window=="three",]$jnd)
      
      sample_var_one <- var(windows[windows$window=="one",]$jnd)
      sample_var_two <- var(windows[windows$window=="two",]$jnd)
      sample_var_three <- var(windows[windows$window=="three",]$jnd)
      
      # mean square due to treatment -- variation in sample means weighted by sample group size
      mst <- ( (8*(sample_mean_one-pop_mean)^2) + (8*(sample_mean_two-pop_mean)^2) + (8*(sample_mean_three-pop_mean)^2) ) / 2
      # mean square due to error
      mse<- ( (7*sample_var_one) + (7*sample_var_two) +  (7*sample_var_three) )/ 21
      
      f_stat <- mst/mse
      estimated_jnd <- pop_mean
    }
    # end staircase early < critical value (stability)
    if(f_stat<2.57456939)
    {
      break
    }
  }
    return(estimated_jnd)
}


simulate.staircase.above <- function(target)
{
  f_stat <- 99
  estimated_jnd <- 0
  start <- 0.95
  num_steps <- (0.95-target)*20+2
  steps <- data.frame(matrix(ncol = 3, nrow = num_steps))
  colnames(steps) <- c("min","max","jnd")
  condition <- num_steps-2
  for(i in 1:num_steps)
  {
    steps[i,1] <- start
    if(i == 1)
    {
       steps[i,2] <- 1
    }
    else
    {
      steps[i,2] <- start+0.01
    }
    
    steps[i,3] <- round(abs(target-start),2)
    if(i < condition)
    {
      start <- start-0.05
    }
    else
    {
      start <- start-0.02
    }
  }
  windows <- data.frame(matrix(ncol = 2, nrow = 24))
  colnames(windows) <- c("window", "jnd") 
  jnd <- 1:50
  jnd[1] <- steps[1,3]
  # starting distance of 2
  if(nrow(steps) < 6)
  {
    row_index <- 1
  }
  else
  {
    row_index <- nrow(steps)-5
  }
  for(j in 1:50)
  {
    answer <- sample(c(1,0), 1)
    if(answer == 1)
    {
      row_index <- row_index+1
      row_index <- min(num_steps, row_index)
      jnd[j+1] <- steps[row_index,3]
    }
    if(answer == 0)
    {
      row_index <- row_index-3
      row_index <- max(1, row_index)
      jnd[j+1] <- steps[row_index,3]
      
    }
    if(j>23)
    {
      counter <- 0
      window_start <- j-23
      for(k in window_start:j)
      {
        counter <- counter+1
        if(counter < 9)
        {
          windows[counter,1] <- "one"
        }
        else if(counter < 17)
        {
          windows[counter,1] <- "two"
        }
        else
        {
          windows[counter,1] <- "three"
        }
        windows[counter,2] <- jnd[k]
      }
      # calculate f-test 
      pop_mean<-mean(windows$jnd)
      sample_mean_one <- mean(windows[windows$window=="one",]$jnd)
      sample_mean_two <- mean(windows[windows$window=="two",]$jnd)
      sample_mean_three <- mean(windows[windows$window=="three",]$jnd)
      
      sample_var_one <- var(windows[windows$window=="one",]$jnd)
      sample_var_two <- var(windows[windows$window=="two",]$jnd)
      sample_var_three <- var(windows[windows$window=="three",]$jnd)
      
      # mean square due to treatment -- variation in sample means weighted by sample group size
      mst <- ( (8*(sample_mean_one - pop_mean)^2) + (8*(sample_mean_two - pop_mean)^2) + (8*(sample_mean_three - pop_mean)^2) ) / 2
      # mean square due to error
      mse<- ( (7*sample_var_one) + (7*sample_var_two) +  (7*sample_var_three) )/ 21
      
      f_stat <- mst/mse
      estimated_jnd <- pop_mean
    }
    #end staircase early if crit reached
    if(f_stat<2.57456939)
    {
      break
    }
  }
  return(estimated_jnd)
}    

# simulate chance
targets <- c(0.9,0.8,0.7,0.6,0.5,0.4,0.3,0.2)
chance_by_condition <- data.frame(matrix(ncol= 3, nrow = 12))
colnames(chance_by_condition) <- c("target", "approach", "jnd24")
for(i in 1:length(targets))
{
  print(targets[i])
  # above
  chance_boundary <-  data.frame(matrix(ncol = 3, nrow = 100))
  for(j in 1:nrow(chance_boundary))
  {
    chance_boundary[j,] <- simulate.staircase.above(targets[i])
  }
  row_index<- i*2-1
  chance_by_condition[row_index,1] <- targets[i]
  chance_by_condition[row_index,2] <- "above"
  chance_by_condition[row_index,3] <- mean(chance_boundary[,1])
  
  #below
  chance_boundary <- data.frame(matrix(ncol= 3, nrow = 100))
  for(j in 1:nrow(chance_boundary))
  {
    chance_boundary[j,] <- simulate.staircase.below(targets[i])
  }
  row_index <- i*2
  chance_by_condition[row_index,1] <- targets[i]
  chance_by_condition[row_index,2] <- "below"
  chance_by_condition[row_index,3] <- mean(chance_boundary[,1])
}

# ---------------------- Harrison's staircase ------------------
# simulate.staircase.below <- function(target)
# {
#   f_stat <- 99
#   estimated_jnd <- 0
#   start <- 0
#   num_steps <- (target*10)*10
#   steps <- data.frame(matrix(ncol=3, nrow=num_steps))
#   colnames(steps) <- c("min","max","jnd")
#   for(i in 1:num_steps)
#   {
#     steps[i,1] <- start
#     steps[i,2] <- start+0.01
#     steps[i,3] <- round(abs(target-start),3)
#     start<-start+0.01
#   }
#   row_index <- nrow(steps)-9
#   windows <- data.frame(matrix(ncol= 2, nrow = 24))
#   colnames(windows) <- c("window", "jnd") 
#   jnd <- 1:50
#   jnd[1] <- steps[1,3]
# #   repeat
# #   {
#     for(j in 1:50)
#     {
#       answer <- sample(c(1,0),1)
#       if(answer == 1)
#       {
#         row_index <- row_index+1
#         row_index <- min(num_steps, row_index)
#         jnd[j+1] <- steps[row_index,3]
#       }
#       if(answer == 0)
#       {
#         row_index <- row_index-3
#         row_index <- max(1, row_index)
#         jnd[j+1] <- steps[row_index,3]
#       }
#       if(j>23)
#       {
#         counter <- 0
#         window_start <- j-23
#         for(k in window_start:j)
#         {
#           counter <- counter+1
#           if(counter < 9)
#           {
#             windows[counter,1] <- "one"
#           }
#           else if(counter < 17)
#           {
#             windows[counter,1] <- "two"
#           }
#           else
#           {
#             windows[counter,1] <- "three"
#           }
#           windows[counter,2] <- jnd[k]
#         }
#         # calculate f-test 
#         pop_mean <- mean(windows$jnd)
#         sample_mean_one <- mean(windows[windows$window=="one",]$jnd)
#         sample_mean_two <- mean(windows[windows$window=="two",]$jnd)
#         sample_mean_three <- mean(windows[windows$window=="three",]$jnd)
#         
#         sample_var_one <- var(windows[windows$window=="one",]$jnd)
#         sample_var_two <- var(windows[windows$window=="two",]$jnd)
#         sample_var_three <- var(windows[windows$window=="three",]$jnd)
#         
#         # mean square due to treatment -- variation in sample means weighted by sample group size
#         mst <- ( (8*(sample_mean_one - pop_mean)^2) + (8*(sample_mean_two - pop_mean)^2) + (8*(sample_mean_three - pop_mean)^2) ) / 2
#         # mean square due to error
#         mse <- ( (7*sample_var_one) + (7*sample_var_two) +  (7*sample_var_three) )/ 21
#         
#         f_stat <- mst/mse
#         estimated_jnd <- pop_mean
#       }
#     # end staircase early if crit reached
#     if(f_stat<2.57456939)
#     {
#      # break
#     }
#   }
#   return(estimated_jnd)
# }
# 
# 
# simulate.staircase.above <- function(target)
# {
#   f_stat <- 99
#   estimated_jnd <- 0
#  
#   num_steps <- ((1-target)*10)*10 
#   steps <- data.frame(matrix(ncol= 3, nrow = num_steps))
#   colnames(steps) <- c("min","max","jnd")
#   condition <- num_steps-2
#   row_index <- nrow(steps)-9
#   start <- 1
#   for(i in 1:num_steps)
#   {
#     steps[i,1] <- start
#     if(i == 1)
#     {
#       # so difficult to reach that we'll accept anything >0.95
#       steps[i,2] <- 1
#     }
#     else
#     {
#       steps[i,2] <- start-0.01
#     }
#     steps[i,3] <- round(abs(target-start),3)
#     start <-start-0.01
#   }
#   windows <- data.frame(matrix(ncol= 2, nrow = 24))
#   colnames(windows) <- c("window", "jnd") 
#   jnd <- 1:50
#   jnd[1] <- steps[1,3]
# 
#     for(j in 1:50)
#     {
#       answer <- sample(c(1,0),1)
#       if(answer == 1)
#       {
#         row_index <- row_index+1
#         row_index <- min(num_steps, row_index)
#         jnd[j+1] <- steps[row_index,3]
#       }
#       if(answer == 0)
#       {
#         row_index <- row_index-3
#         row_index <- max(1, row_index)
#         jnd[j+1] <- steps[row_index,3]
#       }
#       if(j>23)
#       {
#         counter <- 0
#         window_start <- j-23
#         for(k in window_start:j)
#         {
#           counter<-counter+1
#           if(counter < 9)
#           {
#             windows[counter,1] <- "one"
#           }
#           else if(counter < 17)
#           {
#             windows[counter,1] <- "two"
#           }
#           else
#           {
#             windows[counter,1] <- "three"
#           }
#           windows[counter,2] <- jnd[k]
#         }
#         
#         #calculate f-test 
#         pop_mean <- mean(windows$jnd)
#         sample_mean_one <- mean(windows[windows$window=="one",]$jnd)
#         sample_mean_two <- mean(windows[windows$window=="two",]$jnd)
#         sample_mean_three <- mean(windows[windows$window=="three",]$jnd)
#         
#         sample_var_one <- var(windows[windows$window=="one",]$jnd)
#         sample_var_two <- var(windows[windows$window=="two",]$jnd)
#         sample_var_three <- var(windows[windows$window=="three",]$jnd)
#         
#         # mean square due to treatment -- variation in sample means weighted by sample group size
#         mst <- ( (8*(sample_mean_one - pop_mean)^2) + (8*(sample_mean_two - pop_mean)^2) + (8*(sample_mean_three - pop_mean)^2) ) / 2
#         # mean square due to error
#         mse <- ( (7*sample_var_one) + (7*sample_var_two) +  (7*sample_var_three) )/ 21
#         
#         f_stat <- mst/mse
#         estimated_jnd <- pop_mean
#       }
#       # end staircase early if crit reached
#       if(f_stat < 2.57456939)
#       {
#        # break
#       }
#     }
#   return(estimated_jnd)
# }    





