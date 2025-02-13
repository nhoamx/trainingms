import cv2
import numpy as np
import config
import os

# Define the function to detect filled bubbles
def detect_bubbles(img_path, bubble_positions, threshold=150):
    # Load the image in grayscale
    img = cv2.imread(img_path, cv2.IMREAD_GRAYSCALE)
    os.makedirs('output/gray', exist_ok=True)
    cv2.imwrite('output/gray/gray.png', img)

    # Preprocess the image (blurring helps reduce noise)
    blurred_img = cv2.GaussianBlur(img, (5, 5), 0)
    os.makedirs('output/blurred', exist_ok=True)
    cv2.imwrite('output/blurred/blurred.png', blurred_img)

    # Threshold the image to create a binary image
    _, binary_img = cv2.threshold(blurred_img, threshold, 255, cv2.THRESH_BINARY_INV)
    os.makedirs('output/binary', exist_ok=True)
    cv2.imwrite('output/binary/binary.png', binary_img)

    results = {}
    # Loop over each bubble position
    for question, positions in bubble_positions.items():
        selected_option = None
        max_non_white_pixels = 0

        # Check each bubble option for the current question
        for option, pos in positions.items():
            x, y, w, h = pos
            bubble_roi = binary_img[y:y+h, x:x+w]

            # Count the number of non-white pixels in the bubble region
            non_white_pixels = cv2.countNonZero(bubble_roi)

            # If this option has more filled pixels, it's considered filled
            if non_white_pixels > max_non_white_pixels:
                max_non_white_pixels = non_white_pixels
                selected_option = option

        # Record the selected option for each question
        results[question] = selected_option

    return results


# Function to draw bubble positions on the image
def draw_bubble_positions(img_path, bubble_positions, output_path):
    img = cv2.imread(img_path)
    for question, positions in bubble_positions.items():
        print(f"Question {question}:")
        for option, pos in positions.items():
            x, y, w, h = pos
            cv2.rectangle(img, (x, y), (x + w, y + h), (0, 255, 0), 2)
            cv2.putText(img, f"{question}-{option}", (x, y - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 255, 0), 1)
    os.makedirs(os.path.dirname(output_path), exist_ok=True)
    cv2.imwrite(output_path, img)

# Run the function with an image path
img_path = 'test_reference_3.png'
folio = config.folio_configuration
evaluation_01 = config.reference_i

results = detect_bubbles(img_path, evaluation_01)
draw_bubble_positions(img_path, evaluation_01, 'output/output_with_bubbles.png')


# Print the results
for question, answer in results.items():
    print(f"{question}: {answer}")
